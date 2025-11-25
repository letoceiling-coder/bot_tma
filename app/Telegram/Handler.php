<?php

namespace App\Telegram;

use DefStudio\Telegraph\Handlers\WebhookHandler;
use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Stringable;

class Handler extends WebhookHandler
{
    /**
     * Handle unknown commands - implement block flow logic here
     */
    protected function handleUnknownCommand(Stringable $text): void
    {
        // CRITICAL FIX: Check if chat is null before accessing properties
        if (!$this->chat) {
            Log::error('handleUnknownCommand: Chat is null', [
                'text' => $text,
            ]);
            return;
        }

        [$command, $parameter] = $this->parseCommand($text);
        
        Log::info('handleUnknownCommand received', [
            'text' => $text,
            'chat_id' => $this->chat->chat_id ?? null,
            'command' => $command,
            'parameter' => $parameter,
        ]);

        // Implement block flow resolution logic here
        // This is where you'd resolve which block to execute based on the command
        $block = $this->resolveBlock($command, $parameter);
        
        if ($block) {
            // Convert object to array if needed
            $block = is_object($block) ? $this->blockObjectToArray($block) : $block;
            
            Log::info('handleUnknownCommand resolved block', [
                'text' => $text,
                'command_parameter' => $parameter,
                'block' => $block,
            ]);
            
            $this->executeBlock($block);
        } else {
            // Default fallback response
            $this->chat->html('Неизвестная команда')->send();
        }
    }

    /**
     * Handle chat messages (text responses)
     */
    protected function handleChatMessage(Stringable $text): void
    {
        // CRITICAL FIX: Check if chat is null before accessing properties
        if (!$this->chat) {
            Log::error('handleChatMessage: Chat is null', [
                'text' => $text,
                'message_id' => $this->message?->id() ?? null,
            ]);
            return;
        }

        Log::info('handleChatMessage received', [
            'text' => $text,
            'chat_id' => $this->chat->chat_id ?? null,
            'message_id' => $this->message?->id() ?? null,
            'has_reply' => $this->message && $this->message->replyToMessage() ? true : false,
        ]);

        // Get the current block/flow state for the chat
        // This might be stored in session, database, or chat metadata
        $currentBlock = $this->getCurrentBlock();
        
        Log::info('handleChatMessage: Current block retrieved', [
            'currentBlock' => $currentBlock,
            'is_null' => $currentBlock === null,
            'is_array' => is_array($currentBlock),
            'is_object' => is_object($currentBlock),
        ]);
        
        if (!$currentBlock) {
            Log::warning('handleChatMessage: No current block found, skipping processing', [
                'chat_id' => $this->chat->chat_id ?? null,
                'text' => (string) $text,
            ]);
            // Don't return, allow fallback behavior
            $this->chat->html('Пожалуйста, выберите действие из меню выше.')->send();
            return;
        }

        // Ensure currentBlock is an array, not an object
        if (is_object($currentBlock)) {
            $currentBlock = $this->blockObjectToArray($currentBlock);
            Log::info('handleChatMessage: Converted block object to array', [
                'convertedBlock' => $currentBlock,
            ]);
        }

        // Validate block structure before processing
        if (!is_array($currentBlock) || empty($currentBlock['id'])) {
            Log::error('handleChatMessage: Invalid block structure', [
                'currentBlock' => $currentBlock,
                'block_type' => gettype($currentBlock),
            ]);
            return;
        }

        // Process the text response based on the current block
        $this->processBlockResponse($currentBlock, $text);
    }

    /**
     * Handle callback queries from inline buttons
     */
    protected function handleCallbackQuery(): void
    {
        $this->extractCallbackQueryData();

        $action = $this->callbackQuery?->data()->get('action') ?? '';
        
        Log::info('handleCallbackQuery', [
            'action' => $action,
            'data' => $this->data->toArray(),
            'chat_id' => $this->chat?->chat_id ?? null,
        ]);

        // Handle inline button actions
        if ($action === 'actionInlineButtons') {
            $blockId = $this->data->get('id');
            
            if (!$blockId) {
                Log::warning('handleCallbackQuery: No block ID in callback data');
                return;
            }

            Log::info('handleCallbackQuery: Processing actionInlineButtons', [
                'blockId' => $blockId,
            ]);

            $block = $this->getBlockById($blockId);
            
            Log::info('handleCallbackQuery: Block retrieved', [
                'blockId' => $blockId,
                'block' => $block,
                'is_null' => $block === null,
                'is_array' => is_array($block),
                'is_object' => is_object($block),
            ]);
            
            if ($block) {
                // Convert object to array if needed
                $block = is_object($block) ? $this->blockObjectToArray($block) : $block;
                
                Log::info('handleCallbackQuery: Executing block', [
                    'blockId' => $blockId,
                    'blockType' => $block['type'] ?? 'unknown',
                    'block' => $block,
                ]);
                
                try {
                    $this->executeBlock($block);
                    Log::info('handleCallbackQuery: Block executed successfully', [
                        'blockId' => $blockId,
                    ]);
                } catch (\Exception $e) {
                    Log::error('handleCallbackQuery: Error executing block', [
                        'blockId' => $blockId,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
                
                // Answer the callback query to remove loading state
                // In Telegraph, use bot->replyWebhook() to answer callback queries
                if ($this->callbackQuery && $this->bot) {
                    try {
                        $this->bot->replyWebhook($this->callbackQuery->id(), '')->send();
                        Log::info('handleCallbackQuery: Callback query answered');
                    } catch (\Exception $e) {
                        Log::warning('handleCallbackQuery: Error answering callback query', [
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            } else {
                Log::warning('handleCallbackQuery: Block not found', [
                    'blockId' => $blockId,
                ]);
                
                // Answer callback query even if block not found
                if ($this->callbackQuery && $this->bot) {
                    try {
                        $this->bot->replyWebhook($this->callbackQuery->id(), '')->send();
                    } catch (\Exception $e) {
                        // Ignore errors
                    }
                }
            }
        } elseif ($action === 'confirm_input') {
            // Handle confirmation "Да" button
            $blockId = $this->data->get('block_id');
            $pendingData = $this->getChatPendingInput();
            
            Log::info('handleCallbackQuery: Processing confirm_input', [
                'blockId' => $blockId,
                'pendingData' => $pendingData,
            ]);
            
            // Answer callback query FIRST to remove loading state
            if ($this->callbackQuery && $this->bot) {
                try {
                    $this->bot->replyWebhook($this->callbackQuery->id(), '')->send();
                    Log::info('handleCallbackQuery: Callback query answered for confirm_input');
                } catch (\Exception $e) {
                    Log::warning('handleCallbackQuery: Error answering callback query', [
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            
            if ($pendingData && $blockId) {
                $originalBlock = $this->getBlockById($blockId);
                if ($originalBlock) {
                    $originalBlock = is_object($originalBlock) ? $this->blockObjectToArray($originalBlock) : $originalBlock;
                    if (is_array($originalBlock)) {
                        Log::info('handleCallbackQuery: Processing confirmed input', [
                            'originalBlock' => $originalBlock,
                            'userInput' => $pendingData['input'] ?? null,
                        ]);
                        
                        $this->processConfirmedInput($originalBlock, $pendingData['input'] ?? null);
                        $this->clearChatPendingInput();
                    }
                }
            }
        } elseif ($action === 'cancel_input') {
            // Handle cancellation "Нет" button
            $blockId = $this->data->get('block_id');
            $pendingData = $this->getChatPendingInput();
            
            Log::info('handleCallbackQuery: Processing cancel_input', [
                'blockId' => $blockId,
                'pendingData' => $pendingData,
            ]);
            
            // Answer callback query FIRST to remove loading state
            if ($this->callbackQuery && $this->bot) {
                try {
                    $this->bot->replyWebhook($this->callbackQuery->id(), '')->send();
                    Log::info('handleCallbackQuery: Callback query answered for cancel_input');
                } catch (\Exception $e) {
                    Log::warning('handleCallbackQuery: Error answering callback query', [
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            
            if ($pendingData && $blockId) {
                $originalBlock = $this->getBlockById($blockId);
                if ($originalBlock) {
                    $originalBlock = is_object($originalBlock) ? $this->blockObjectToArray($originalBlock) : $originalBlock;
                    if (is_array($originalBlock)) {
                        // Return to input block
                        $this->setChatCurrentBlockId($blockId);
                        $this->executeBlock($originalBlock);
                        $this->clearChatPendingInput();
                    }
                }
            }
        } elseif ($action === 'actionAnswer') {
            // Handle answer confirmation (Да/Нет) - format: action:actionAnswer;id:1 or id:0
            $answerId = $this->data->get('id');
            $isConfirmed = $answerId == 1 || $answerId === '1';
            
            Log::info('handleCallbackQuery: Processing actionAnswer', [
                'answerId' => $answerId,
                'isConfirmed' => $isConfirmed,
                'data' => $this->data->toArray(),
            ]);
            
            // Answer callback query FIRST
            if ($this->callbackQuery && $this->bot) {
                try {
                    $this->bot->replyWebhook($this->callbackQuery->id(), '')->send();
                    Log::info('handleCallbackQuery: Callback query answered for actionAnswer');
                } catch (\Exception $e) {
                    Log::warning('handleCallbackQuery: Error answering callback query', [
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            
            // Get pending input and current block
            $pendingData = $this->getChatPendingInput();
            $currentBlockId = $this->getChatCurrentBlockId();
            
            Log::info('handleCallbackQuery: Processing actionAnswer confirmation', [
                'isConfirmed' => $isConfirmed,
                'pendingData' => $pendingData,
                'currentBlockId' => $currentBlockId,
            ]);
            
            if ($isConfirmed && $pendingData) {
                // User confirmed - proceed to next block
                $originalBlockId = $pendingData['block_id'] ?? null;
                $userInput = $pendingData['input'] ?? null;
                
                if ($originalBlockId) {
                    $originalBlock = $this->getBlockById($originalBlockId);
                    if ($originalBlock) {
                        $originalBlock = is_object($originalBlock) ? $this->blockObjectToArray($originalBlock) : $originalBlock;
                        if (is_array($originalBlock) && !empty($originalBlock['id'])) {
                            Log::info('handleCallbackQuery: Processing confirmed answer, proceeding to next block', [
                                'originalBlockId' => $originalBlockId,
                                'originalBlock' => $originalBlock,
                                'next_block' => $originalBlock['next_block'] ?? null,
                                'userInput' => $userInput,
                            ]);
                            
                            // CRITICAL: Use the original block's next_block to continue the flow
                            $this->processConfirmedInput($originalBlock, $userInput);
                            $this->clearChatPendingInput();
                        } else {
                            Log::warning('handleCallbackQuery: Original block missing ID or not an array', [
                                'originalBlockId' => $originalBlockId,
                                'originalBlock' => $originalBlock,
                                'is_array' => is_array($originalBlock),
                            ]);
                        }
                    } else {
                        Log::warning('handleCallbackQuery: Original block not found', [
                            'originalBlockId' => $originalBlockId,
                        ]);
                    }
                }
            } elseif (!$isConfirmed && $pendingData) {
                // User cancelled - return to input block
                $originalBlockId = $pendingData['block_id'] ?? null;
                
                if ($originalBlockId) {
                    $originalBlock = $this->getBlockById($originalBlockId);
                    if ($originalBlock) {
                        $originalBlock = is_object($originalBlock) ? $this->blockObjectToArray($originalBlock) : $originalBlock;
                        if (is_array($originalBlock)) {
                            Log::info('handleCallbackQuery: Answer cancelled, returning to input block', [
                                'originalBlockId' => $originalBlockId,
                            ]);
                            // Return to input block
                            $this->setChatCurrentBlockId($originalBlockId);
                            $this->executeBlock($originalBlock);
                            $this->clearChatPendingInput();
                        }
                    }
                }
            }
        } else {
            // Call parent handler for other actions
            parent::handleCallbackQuery();
        }
    }

    /**
     * Resolve block by command and parameter
     */
    protected function resolveBlock(string $command, ?string $parameter = null): ?array
    {
        // This is a placeholder - implement your actual block resolution logic
        // For example, checking a database table for blocks with matching command
        
        // Example: If command is /start, return the start block
        if ($command === 'start') {
            return [
                'id' => 'index-1',
                'type' => 'command',
                'value' => 'Здравствуйте! Меня зовут Олег, и я виртуальный помощник компании "Центр бухгалтерского учета".',
                'target' => 'index-3',
            ];
        }

        return null;
    }

    /**
     * Get block by ID
     */
    protected function getBlockById(string $blockId): ?array
    {
        // Placeholder - implement your actual block retrieval logic
        // This could query a database or load from configuration
        
        // Example blocks mapping
        $blocks = [
            'index-3' => [
                'id' => 'index-3',
                'type' => 'menu',
                'text' => 'Выберите направление',
                'buttons' => [
                    ['text' => 'Создать/изменить/закрыть бизнес', 'id' => 'index-4'],
                    ['text' => 'Бухгалтерия и отчетность', 'id' => 'index-7'],
                    ['text' => 'Судебное сопровождение', 'id' => 'index-8'],
                    ['text' => 'Блокировка счета (115-ФЗ/161-ФЗ)', 'id' => 'index-8'],
                    ['text' => 'Лицензии и сертификаты', 'id' => 'index-9'],
                    ['text' => 'Товары и гранты', 'id' => 'index-10'],
                    ['text' => 'Связаться с менеджером', 'id' => 'index-11'],
                ],
            ],
            'index-4' => [
                'id' => 'index-4',
                'type' => 'menu',
                'text' => 'Отлично! Выберете нужный запрос.',
                'buttons' => [
                    ['text' => 'Внести изменения в компанию', 'id' => 'index-5'],
                    ['text' => 'Ликвидировать компанию', 'id' => 'index-7'],
                ],
            ],
            'index-5' => [
                'id' => 'index-5',
                'type' => 'input',
                'text' => 'Как к вам обращаться (ФИО)',
                'input_type' => 'text',
                'confirmation_block' => 'index-5-confirm', // Block for confirmation
                'next_block' => 'index-6', // Next block after confirmation
            ],
            'index-5-confirm' => [
                'id' => 'index-5-confirm',
                'type' => 'confirmation',
                'text' => 'Пожалуйста, подтвердите ваш ответ: {user_input}',
                'confirm_button' => 'Да, верно',
                'cancel_button' => 'Нет, исправить',
                'confirm_action' => 'confirm_input', // Action for "Да"
                'cancel_action' => 'cancel_input',   // Action for "Нет"
            ],
            'index-6' => [
                'id' => 'index-6',
                'type' => 'text',
                'text' => 'Спасибо! Ваши данные сохранены. Мы свяжемся с вами в ближайшее время.',
            ],
        ];

        return $blocks[$blockId] ?? null;
    }

    /**
     * Get current block for the chat
     */
    protected function getCurrentBlock(): ?array
    {
        // CRITICAL FIX: Check if message exists
        if (!$this->message) {
            Log::warning('getCurrentBlock: Message is null');
            return null;
        }

        // Check if message is a reply to bot's message
        $replyTo = $this->message->replyToMessage();
        
        if ($replyTo) {
            // Check if this is a reply to bot's message asking for input
            $replyText = $replyTo->text() ?? '';
            
            Log::info('getCurrentBlock: Reply detected', [
                'replyText' => $replyText,
            ]);
            
            // Try to determine block from reply text
            // If reply text is "Как к вам обращаться (ФИО)", it's block index-5
            if (str_contains($replyText, 'Как к вам обращаться')) {
                Log::info('getCurrentBlock: Detected "Как к вам обращаться" in reply text, fetching index-5');
                $block = $this->getBlockById('index-5');
                Log::info('getCurrentBlock: Fetched block index-5', [
                    'block' => $block,
                    'is_null' => $block === null,
                    'is_array' => is_array($block),
                    'is_object' => is_object($block),
                ]);
                if ($block !== null) {
                    $result = is_object($block) ? $this->blockObjectToArray($block) : $block;
                    
                    // CRITICAL FIX: Validate result has id before returning
                    if (is_array($result) && !empty($result['id'])) {
                        Log::info('getCurrentBlock: Returning block index-5', [
                            'result' => $result,
                            'result_id' => $result['id'] ?? 'NO ID',
                        ]);
                        return $result;
                    } else {
                        Log::warning('getCurrentBlock: Block index-5 retrieved but missing ID', [
                            'result' => $result,
                            'is_array' => is_array($result),
                        ]);
                    }
                } else {
                    Log::warning('getCurrentBlock: Block index-5 not found in getBlockById');
                }
            }
            
            // Try to get current block from chat storage
            $currentBlockId = $this->getChatCurrentBlockId();
            if ($currentBlockId) {
                Log::info('getCurrentBlock: Retrieving block from storage (reply context)', [
                    'currentBlockId' => $currentBlockId,
                ]);
                
                $block = $this->getBlockById($currentBlockId);
                
                // CRITICAL FIX: Check if block is null before accessing properties
                if ($block !== null) {
                    // Convert object to array if needed
                    $convertedBlock = is_object($block) ? $this->blockObjectToArray($block) : $block;
                    
                    // Validate converted block has id
                    if (is_array($convertedBlock) && !empty($convertedBlock['id'])) {
                        Log::info('getCurrentBlock: Block retrieved from storage (reply context)', [
                            'currentBlockId' => $currentBlockId,
                            'block' => $convertedBlock,
                        ]);
                        return $convertedBlock;
                    } else {
                        Log::warning('getCurrentBlock: Block retrieved but missing ID (reply context)', [
                            'currentBlockId' => $currentBlockId,
                            'convertedBlock' => $convertedBlock,
                        ]);
                    }
                } else {
                    Log::warning('getCurrentBlock: Block not found in getBlockById (reply context)', [
                        'currentBlockId' => $currentBlockId,
                    ]);
                }
            }
        } else {
            // Not a reply, try to get from chat storage
            $currentBlockId = $this->getChatCurrentBlockId();
            if ($currentBlockId) {
                Log::info('getCurrentBlock: Retrieving block from storage', [
                    'currentBlockId' => $currentBlockId,
                ]);
                
                $block = $this->getBlockById($currentBlockId);
                
                // CRITICAL FIX: Check if block is null before accessing properties
                if ($block !== null) {
                    // Convert object to array if needed
                    $convertedBlock = is_object($block) ? $this->blockObjectToArray($block) : $block;
                    
                    // Validate converted block has id
                    if (is_array($convertedBlock) && !empty($convertedBlock['id'])) {
                        Log::info('getCurrentBlock: Block retrieved from storage', [
                            'currentBlockId' => $currentBlockId,
                            'block' => $convertedBlock,
                        ]);
                        return $convertedBlock;
                    } else {
                        Log::warning('getCurrentBlock: Block retrieved but missing ID', [
                            'currentBlockId' => $currentBlockId,
                            'convertedBlock' => $convertedBlock,
                        ]);
                    }
                } else {
                    Log::warning('getCurrentBlock: Block not found in getBlockById', [
                        'currentBlockId' => $currentBlockId,
                    ]);
                }
            }
        }
        
        return null;
    }

    /**
     * Get current block ID from chat metadata (using storage)
     */
    protected function getChatCurrentBlockId(): ?string
    {
        if (!$this->chat) {
            return null;
        }

        try {
            // Use TelegraphChat storage to get current block ID
            $storage = $this->chat->storage();
            $blockId = $storage->get('current_block_id');
            
            // Validate that we got a valid string
            if (!empty($blockId) && is_string($blockId)) {
                return $blockId;
            }
            
            return null;
        } catch (\JsonException $e) {
            // JSON syntax error - file might be empty or corrupted
            Log::warning('getChatCurrentBlockId: JSON syntax error in storage file', [
                'error' => $e->getMessage(),
            ]);
            // Try to recover by clearing corrupted storage
            try {
                $storage = $this->chat->storage();
                $storage->forget('current_block_id');
            } catch (\Exception $e2) {
                // Ignore errors when trying to clear
            }
            return null;
        } catch (\Exception $e) {
            Log::warning('getChatCurrentBlockId: Failed to get from storage', [
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
            ]);
            return null;
        }
    }

    /**
     * Set current block ID in chat metadata (using storage)
     */
    protected function setChatCurrentBlockId(?string $blockId): void
    {
        if (!$this->chat) {
            return;
        }

        try {
            $storage = $this->chat->storage();
            if ($blockId && !empty($blockId)) {
                $storage->set('current_block_id', (string) $blockId);
            } else {
                // Clear the storage if blockId is null or empty
                try {
                    $storage->forget('current_block_id');
                } catch (\Exception $e) {
                    // Ignore errors when forgetting
                }
            }
        } catch (\JsonException $e) {
            // JSON syntax error - try to recover
            Log::warning('setChatCurrentBlockId: JSON syntax error, attempting recovery', [
                'error' => $e->getMessage(),
                'blockId' => $blockId,
            ]);
            // Don't throw - just log the error
        } catch (\Exception $e) {
            Log::warning('setChatCurrentBlockId: Failed to save to storage', [
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
                'blockId' => $blockId,
            ]);
            // Don't throw - storage errors shouldn't break the flow
        }
    }

    /**
     * Set pending input (user input waiting for confirmation)
     */
    protected function setChatPendingInput(string $blockId, string $userInput): void
    {
        if (!$this->chat) {
            return;
        }

        try {
            $storage = $this->chat->storage();
            $storage->set('pending_input', [
                'block_id' => $blockId,
                'input' => $userInput,
            ]);
        } catch (\Exception $e) {
            Log::warning('setChatPendingInput: Failed to save to storage', [
                'error' => $e->getMessage(),
                'blockId' => $blockId,
                'userInput' => $userInput,
            ]);
        }
    }

    /**
     * Get pending input
     */
    protected function getChatPendingInput(): ?array
    {
        if (!$this->chat) {
            return null;
        }

        try {
            $storage = $this->chat->storage();
            $pendingInput = $storage->get('pending_input');
            
            if (is_array($pendingInput) && !empty($pendingInput['block_id']) && isset($pendingInput['input'])) {
                return $pendingInput;
            }
            
            return null;
        } catch (\Exception $e) {
            Log::warning('getChatPendingInput: Failed to get from storage', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Clear pending input
     */
    protected function clearChatPendingInput(): void
    {
        if (!$this->chat) {
            return;
        }

        try {
            $storage = $this->chat->storage();
            $storage->forget('pending_input');
        } catch (\Exception $e) {
            // Ignore errors
        }
    }

    /**
     * Execute confirmation block with user input
     */
    protected function executeConfirmationBlock(array $confirmationBlock, string $userInput, array $originalBlock): void
    {
        if (!$this->chat) {
            return;
        }

        // Support both formats: placeholder format and question:answer format
        $textTemplate = $confirmationBlock['text'] ?? 'Пожалуйста, подтвердите ваш ответ: {user_input}';
        $originalBlockText = $originalBlock['text'] ?? 'Ваш ответ';
        
        // Replace placeholder {user_input} with actual user input
        $text = str_replace('{user_input}', $userInput, $textTemplate);
        
        // If text template doesn't have placeholder, use format: "Question:answer"
        if (!str_contains($textTemplate, '{user_input}')) {
            // Format like "Как к вам обращаться (ФИО):Никита"
            $text = $originalBlockText . ':' . $userInput;
        }
        
        $confirmButton = $confirmationBlock['confirm_button'] ?? 'Да';
        $cancelButton = $confirmationBlock['cancel_button'] ?? 'Нет';
        
        Log::info('executeConfirmationBlock: Creating confirmation block', [
            'text' => $text,
            'userInput' => $userInput,
            'originalBlockId' => $originalBlock['id'] ?? null,
        ]);
        
        // Create keyboard with Yes/No buttons
        // Support both formats: confirm_input/cancel_input and actionAnswer
        $keyboard = Keyboard::make();
        
        // Use actionAnswer format (like on first account) for compatibility
        $confirmBtn = Button::make($confirmButton)
            ->action('actionAnswer')
            ->param('id', '1'); // 1 = confirmed
        
        $cancelBtn = Button::make($cancelButton)
            ->action('actionAnswer')
            ->param('id', '0'); // 0 = cancelled
        
        // Each button on its own row
        $keyboard->row([$confirmBtn]);
        $keyboard->row([$cancelBtn]);
        
        try {
            $this->chat->html($text)->keyboard($keyboard)->send();
            Log::info('executeConfirmationBlock: Confirmation block sent successfully', [
                'text' => $text,
            ]);
        } catch (\Exception $e) {
            Log::error('executeConfirmationBlock: Error sending confirmation block', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'text' => $text,
            ]);
        }
    }

    /**
     * Process confirmed input - proceed to next block
     */
    protected function processConfirmedInput(array $inputBlock, ?string $userInput): void
    {
        if (!$this->chat) {
            return;
        }

        // CRITICAL: Get next_block from the ORIGINAL input block (the question block)
        $nextBlockId = $inputBlock['next_block'] ?? null;
        
        Log::info('processConfirmedInput: Processing confirmed input', [
            'userInput' => $userInput,
            'nextBlockId' => $nextBlockId,
            'inputBlockId' => $inputBlock['id'] ?? null,
            'inputBlock' => $inputBlock, // Log full block to see all properties
        ]);
        
        // Clear current block from metadata
        $this->setChatCurrentBlockId(null);
        
        // CRITICAL: The next_block should come from the original input block, not from confirmation block
        if ($nextBlockId) {
            Log::info('processConfirmedInput: Next block ID found in input block', [
                'nextBlockId' => $nextBlockId,
                'inputBlockId' => $inputBlock['id'] ?? null,
            ]);
            
            $nextBlock = $this->getBlockById($nextBlockId);
            
            Log::info('processConfirmedInput: Retrieved next block', [
                'nextBlockId' => $nextBlockId,
                'nextBlock' => $nextBlock,
                'is_null' => $nextBlock === null,
            ]);
            
            if ($nextBlock) {
                $nextBlock = is_object($nextBlock) ? $this->blockObjectToArray($nextBlock) : $nextBlock;
                
                if (is_array($nextBlock) && !empty($nextBlock['id'])) {
                    Log::info('processConfirmedInput: Executing next block from input block\'s next_block', [
                        'nextBlock' => $nextBlock,
                        'nextBlock_id' => $nextBlock['id'] ?? null,
                        'nextBlock_type' => $nextBlock['type'] ?? 'unknown',
                        'nextBlock_text' => $nextBlock['text'] ?? $nextBlock['value'] ?? 'no text',
                        'inputBlockId' => $inputBlock['id'] ?? null,
                    ]);
                    
                    try {
                        // Execute the next block specified in the original input block
                        $this->executeBlock($nextBlock);
                        Log::info('processConfirmedInput: Next block executed successfully', [
                            'nextBlockId' => $nextBlockId,
                            'nextBlock_id' => $nextBlock['id'] ?? null,
                        ]);
                    } catch (\Exception $e) {
                        Log::error('processConfirmedInput: Error executing next block', [
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                            'nextBlockId' => $nextBlockId,
                        ]);
                    }
                } else {
                    Log::warning('processConfirmedInput: Next block missing ID or not an array', [
                        'nextBlock' => $nextBlock,
                        'is_array' => is_array($nextBlock),
                        'has_id' => isset($nextBlock['id']) && !empty($nextBlock['id']),
                    ]);
                }
            } else {
                Log::warning('processConfirmedInput: Next block not found in getBlockById', [
                    'nextBlockId' => $nextBlockId,
                    'inputBlockId' => $inputBlock['id'] ?? null,
                ]);
            }
        } else {
            Log::warning('processConfirmedInput: No next_block specified in input block', [
                'inputBlock' => $inputBlock,
                'inputBlock_keys' => array_keys($inputBlock),
                'has_next_block' => isset($inputBlock['next_block']),
            ]);
        }
    }

    /**
     * Convert block object to array (if block comes as object from DB/logs)
     */
    protected function blockObjectToArray($block): array
    {
        // CRITICAL FIX: Check if block is null
        if ($block === null) {
            Log::warning('blockObjectToArray: Block is null');
            return [];
        }

        if (is_array($block)) {
            return $block;
        }

        if (is_object($block)) {
            // Handle object with properties
            $result = [];
            
            // CRITICAL FIX: Use property_exists or try-catch to safely access properties
            try {
                // Use reflection or property_exists to safely check properties
                $reflection = new \ReflectionClass($block);
                $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);
                
                foreach ($properties as $property) {
                    $propertyName = $property->getName();
                    $result[$propertyName] = $block->$propertyName ?? null;
                }
                
                // Also check magic properties - CRITICAL FIX: Use isset to check if property exists and is not null
                // Double-check: property_exists AND isset to avoid "Attempt to read property on null" errors
                try {
                    if (property_exists($block, 'id') && isset($block->id) && $block->id !== null) {
                        $result['id'] = $block->id;
                    }
                } catch (\Throwable $e) {
                    Log::warning('blockObjectToArray: Error accessing block->id', [
                        'error' => $e->getMessage(),
                    ]);
                }
                
                try {
                    if (property_exists($block, 'type') && isset($block->type)) {
                        $result['type'] = $block->type;
                    }
                } catch (\Throwable $e) {
                    // Ignore
                }
                
                try {
                    if (property_exists($block, 'value') && isset($block->value)) {
                        $result['value'] = $block->value;
                    }
                } catch (\Throwable $e) {
                    // Ignore
                }
                
                try {
                    if (property_exists($block, 'text') && isset($block->text)) {
                        $result['text'] = $block->text;
                    }
                } catch (\Throwable $e) {
                    // Ignore
                }
                
                try {
                    if (property_exists($block, 'target') && isset($block->target)) {
                        $result['target'] = $block->target;
                    }
                } catch (\Throwable $e) {
                    // Ignore
                }
                
                try {
                    if (property_exists($block, 'next_block') && isset($block->next_block)) {
                        $result['next_block'] = $block->next_block;
                    }
                } catch (\Throwable $e) {
                    // Ignore
                }
                
                try {
                    if (property_exists($block, 'confirmation_block') && isset($block->confirmation_block)) {
                        $result['confirmation_block'] = $block->confirmation_block;
                    }
                } catch (\Throwable $e) {
                    // Ignore
                }
                
                try {
                    if (property_exists($block, 'buttons') && isset($block->buttons)) {
                        $result['buttons'] = $block->buttons;
                    }
                } catch (\Throwable $e) {
                    // Ignore
                }
            } catch (\Exception $e) {
                // Fallback: try direct access with error suppression
                Log::warning('blockObjectToArray: Error converting object', [
                    'error' => $e->getMessage(),
                    'block_type' => get_class($block),
                ]);
                
                // Try simple property access with checks
                $result['id'] = property_exists($block, 'id') ? ($block->id ?? null) : null;
                $result['type'] = property_exists($block, 'type') ? ($block->type ?? null) : null;
                $result['value'] = property_exists($block, 'value') ? ($block->value ?? null) : null;
                $result['text'] = property_exists($block, 'text') ? ($block->text ?? null) : null;
                $result['target'] = property_exists($block, 'target') ? ($block->target ?? null) : null;
                $result['next_block'] = property_exists($block, 'next_block') ? ($block->next_block ?? null) : null;
                $result['buttons'] = property_exists($block, 'buttons') ? ($block->buttons ?? null) : null;
            }
            
            return $result;
        }

        return [];
    }

    /**
     * Execute a block
     */
    protected function executeBlock(array $block): void
    {
        // CRITICAL FIX: Check if chat is null before accessing properties
        if (!$this->chat) {
            Log::error('executeBlock: Chat is null', ['block' => $block]);
            return;
        }

        if (empty($block['id'])) {
            Log::error('executeBlock: Block missing ID', ['block' => $block]);
            return;
        }

        $blockId = $block['id'];
        $blockType = $block['type'] ?? 'text';

        switch ($blockType) {
            case 'command':
            case 'text':
                $text = $block['text'] ?? $block['value'] ?? '';
                $target = $block['target'] ?? null;
                $nextBlockId = $block['next_block'] ?? null; // Support next_block for text blocks too
                
                Log::info('executeBlock: Executing text/command block', [
                    'blockId' => $blockId,
                    'blockType' => $blockType,
                    'text' => $text,
                    'target' => $target,
                    'nextBlockId' => $nextBlockId,
                    'text_length' => strlen($text),
                ]);
                
                if (!empty($text)) {
                    try {
                        // Send text message
                        $this->chat->html($text)->send();
                        Log::info('executeBlock: Text block sent successfully', [
                            'blockId' => $blockId,
                            'text' => $text,
                        ]);
                        
                        // After sending text, check if there's a target or next_block to execute
                        $blockToExecute = null;
                        
                        if ($target) {
                            // Use target (old way)
                            Log::info('executeBlock: Text sent, fetching target block', [
                                'target' => $target,
                            ]);
                            $blockToExecute = $this->getBlockById($target);
                        } elseif ($nextBlockId) {
                            // Use next_block (new way)
                            Log::info('executeBlock: Text sent, fetching next_block', [
                                'nextBlockId' => $nextBlockId,
                            ]);
                            $blockToExecute = $this->getBlockById($nextBlockId);
                        }
                        
                        if ($blockToExecute) {
                            // Convert object to array if needed
                            $blockToExecute = is_object($blockToExecute) ? $this->blockObjectToArray($blockToExecute) : $blockToExecute;
                            
                            if (is_array($blockToExecute) && !empty($blockToExecute['id'])) {
                                Log::info('executeBlock: Executing next block after text', [
                                    'nextBlockId' => $blockToExecute['id'],
                                    'nextBlockType' => $blockToExecute['type'] ?? 'unknown',
                                    'nextBlock' => $blockToExecute,
                                ]);
                                
                                try {
                                    $this->executeBlock($blockToExecute);
                                    Log::info('executeBlock: Next block after text executed successfully', [
                                        'nextBlockId' => $blockToExecute['id'],
                                    ]);
                                } catch (\Exception $e) {
                                    Log::error('executeBlock: Error executing next block after text', [
                                        'error' => $e->getMessage(),
                                        'trace' => $e->getTraceAsString(),
                                        'nextBlockId' => $blockToExecute['id'] ?? null,
                                    ]);
                                }
                            } else {
                                Log::warning('executeBlock: Next block after text missing ID or not an array', [
                                    'blockToExecute' => $blockToExecute,
                                    'is_array' => is_array($blockToExecute),
                                ]);
                            }
                        } else {
                            if ($target || $nextBlockId) {
                                Log::warning('executeBlock: Target/next_block block not found', [
                                    'target' => $target,
                                    'nextBlockId' => $nextBlockId,
                                ]);
                            } else {
                                Log::info('executeBlock: Text block sent, no target or next_block specified', [
                                    'blockId' => $blockId,
                                ]);
                            }
                        }
                    } catch (\Exception $e) {
                        Log::error('executeBlock: Error sending text block', [
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                            'block' => $block,
                        ]);
                    }
                } else {
                    Log::warning('executeBlock: Empty text in text/command block', [
                        'block' => $block,
                    ]);
                }
                break;

            case 'menu':
                $text = $block['text'] ?? '';
                $buttons = $block['buttons'] ?? [];
                
                $keyboard = Keyboard::make();
                
                // Create a row for each button (each button on its own row)
                foreach ($buttons as $button) {
                    if (!empty($button['id'])) {
                        $buttonObj = Button::make($button['text'])
                            ->action('actionInlineButtons')
                            ->param('id', $button['id']);
                        
                        // Add button as a new row (each button on its own row)
                        $keyboard->row([$buttonObj]);
                    }
                }
                
                $this->chat->html($text)->keyboard($keyboard)->send();
                
                // Store current block state if needed
                // $this->setChatCurrentBlock($blockId);
                break;

            case 'input':
                $text = $block['text'] ?? '';
                $nextBlockId = $block['next_block'] ?? null;
                $confirmationBlockId = $block['confirmation_block'] ?? null;
                
                Log::info('executeBlock: Executing input block', [
                    'blockId' => $blockId,
                    'text' => $text,
                    'nextBlockId' => $nextBlockId,
                    'confirmationBlockId' => $confirmationBlockId,
                ]);
                
                if (!empty($text)) {
                    try {
                        $this->chat->html($text)->send();
                        Log::info('executeBlock: Input block message sent successfully', [
                            'blockId' => $blockId,
                            'text' => $text,
                        ]);
                        
                        // Store the expected input state and next block in chat metadata
                        // This allows us to know which block is waiting for user input
                        if ($blockId) {
                            $this->setChatCurrentBlockId($blockId);
                            Log::info('executeBlock: Current block ID stored', [
                                'blockId' => $blockId,
                            ]);
                        }
                    } catch (\Exception $e) {
                        Log::error('executeBlock: Error sending input block message', [
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                            'block' => $block,
                        ]);
                    }
                } else {
                    Log::warning('executeBlock: Empty text in input block', [
                        'block' => $block,
                    ]);
                }
                break;
        }
    }

    /**
     * Process block response (user input)
     */
    protected function processBlockResponse(array $currentBlock, Stringable $text): void
    {
        // CRITICAL FIX: Check if chat is null before accessing properties
        if (!$this->chat) {
            Log::error('processBlockResponse: Chat is null', [
                'currentBlock' => $currentBlock,
                'text' => $text,
            ]);
            return;
        }

        // CRITICAL FIX: Validate that currentBlock is an array
        if (!is_array($currentBlock)) {
            Log::error('processBlockResponse: currentBlock is not an array', [
                'currentBlock' => $currentBlock,
                'currentBlock_type' => gettype($currentBlock),
                'text' => $text,
            ]);
            return;
        }

        // This is where you'd process the user's text input
        // based on the current block type and configuration
        
        $blockType = $currentBlock['type'] ?? null;
        $blockId = $currentBlock['id'] ?? null;
        
        // Add null check to prevent the error
        if (!$blockId) {
            Log::error('processBlockResponse: Block missing ID', [
                'currentBlock' => $currentBlock,
                'currentBlock_keys' => is_array($currentBlock) ? array_keys($currentBlock) : 'not array',
                'text' => $text,
            ]);
            return;
        }

        Log::info('processBlockResponse: Processing response', [
            'blockId' => $blockId,
            'blockType' => $blockType,
            'text' => (string) $text,
        ]);

        switch ($blockType) {
            case 'input':
                // Store the user's input temporarily for confirmation
                $userInput = (string) $text;
                $confirmationBlockId = $currentBlock['confirmation_block'] ?? null;
                
                Log::info('processBlockResponse: Processing input response', [
                    'userInput' => $userInput,
                    'confirmationBlockId' => $confirmationBlockId,
                    'currentBlockId' => $blockId,
                ]);
                
                // Save user input to storage temporarily for confirmation
                $this->setChatPendingInput($blockId, $userInput);
                
                // Show confirmation block if exists, otherwise go directly to next_block
                if ($confirmationBlockId) {
                    $confirmationBlock = $this->getBlockById($confirmationBlockId);
                    
                    if ($confirmationBlock) {
                        $confirmationBlock = is_object($confirmationBlock) ? $this->blockObjectToArray($confirmationBlock) : $confirmationBlock;
                        
                        if (is_array($confirmationBlock) && !empty($confirmationBlock['id'])) {
                            Log::info('processBlockResponse: Showing confirmation block', [
                                'confirmationBlock' => $confirmationBlock,
                            ]);
                            
                            // Set current block to confirmation block
                            $this->setChatCurrentBlockId($confirmationBlockId);
                            
                            // Execute confirmation block with user input
                            $this->executeConfirmationBlock($confirmationBlock, $userInput, $currentBlock);
                        } else {
                            // Fallback: go directly to next_block
                            $this->processConfirmedInput($currentBlock, $userInput);
                        }
                    } else {
                        // No confirmation block, go directly to next_block
                        $this->processConfirmedInput($currentBlock, $userInput);
                    }
                } else {
                    // No confirmation needed, go directly to next_block
                    $this->processConfirmedInput($currentBlock, $userInput);
                }
                break;
                
            case 'confirmation':
                // Handle confirmation response (Да/Нет)
                $confirmAction = $currentBlock['confirm_action'] ?? null;
                $cancelAction = $currentBlock['cancel_action'] ?? null;
                
                Log::info('processBlockResponse: Processing confirmation response', [
                    'text' => (string) $text,
                    'confirmAction' => $confirmAction,
                    'cancelAction' => $cancelAction,
                ]);
                
                // Check if user confirmed (Да) or cancelled (Нет)
                $textLower = mb_strtolower(trim((string) $text));
                $isConfirmed = in_array($textLower, ['да', 'yes', 'верно', 'подтверждаю', 'подтвердить']);
                $isCancelled = in_array($textLower, ['нет', 'no', 'исправить', 'отмена', 'отменить']);
                
                // Get pending input and original block
                $pendingData = $this->getChatPendingInput();
                
                if ($isConfirmed && $pendingData) {
                    // User confirmed - proceed to next_block
                    $originalBlockId = $pendingData['block_id'] ?? null;
                    $userInput = $pendingData['input'] ?? null;
                    
                    if ($originalBlockId) {
                        $originalBlock = $this->getBlockById($originalBlockId);
                        if ($originalBlock) {
                            $originalBlock = is_object($originalBlock) ? $this->blockObjectToArray($originalBlock) : $originalBlock;
                            if (is_array($originalBlock)) {
                                $this->processConfirmedInput($originalBlock, $userInput);
                                // Clear pending input
                                $this->clearChatPendingInput();
                            }
                        }
                    }
                } elseif ($isCancelled && $pendingData) {
                    // User cancelled - return to input block
                    $originalBlockId = $pendingData['block_id'] ?? null;
                    
                    if ($originalBlockId) {
                        $originalBlock = $this->getBlockById($originalBlockId);
                        if ($originalBlock) {
                            $originalBlock = is_object($originalBlock) ? $this->blockObjectToArray($originalBlock) : $originalBlock;
                            if (is_array($originalBlock)) {
                                // Return to input block
                                $this->setChatCurrentBlockId($originalBlockId);
                                $this->executeBlock($originalBlock);
                                // Clear pending input
                                $this->clearChatPendingInput();
                            }
                        }
                    }
                }
                break;
        }
    }
}
