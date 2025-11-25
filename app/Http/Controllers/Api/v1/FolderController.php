<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Filters\FolderFilter;
use App\Http\Requests\StoreFolderRequest;
use App\Http\Requests\UpdateFolderRequest;
use App\Http\Resources\FolderResource;
use App\Models\Folder;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Tag(
 *     name="Folders",
 *     description="API для управления папками медиа-менеджера"
 * )
 */
class FolderController extends Controller
{
    /**
     * Конструктор контроллера
     * 
     * Middleware auth:api закомментирован для разработки
     * В продакшене раскомментировать!
     */
    public function __construct()
    {
        // TODO: Раскомментировать в продакшене
        // $this->middleware('auth:api', ['only' => ['store', 'update', 'destroy']]);
    }
    /**
     * Получить список папок
     * 
     * @OA\Get(
     *     path="/folders",
     *     tags={"Folders"},
     *     summary="Получить список папок",
     *     description="Возвращает список папок с возможностью фильтрации по родительской папке и пагинации",
     *     @OA\Parameter(
     *         name="parent_id",
     *         in="query",
     *         description="ID родительской папки (null для корневых папок)",
     *         required=false,
     *         @OA\Schema(type="integer", nullable=true)
     *     ),
     *     @OA\Parameter(
     *         name="paginate",
     *         in="query",
     *         description="Количество элементов на странице (0 - без пагинации)",
     *         required=false,
     *         @OA\Schema(type="integer", default=0)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Номер страницы",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Успешный ответ",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Folder"))
     *         )
     *     )
     * )
     * 
     * @param Request $request
     * @return AnonymousResourceCollection
     * @throws BindingResolutionException
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filter = app()->make(FolderFilter::class, ['queryParams' => array_filter($request->all())]);

        if ($request->has('paginate') && $request->get('paginate') != 0) {
            return FolderResource::collection(
                Folder::filter($filter)
                    ->paginate(
                        $request->get('paginate'), 
                        ['*'], 
                        'page', 
                        $request->get('page') ?? 1
                    )
            );
        }

        // Получаем папки с дочерними элементами
        $query = Folder::filter($filter)->with('children');

        // Фильтрация по родительской папке
        if ($request->has('parent_id')) {
            $parentId = $request->get('parent_id');
            if ($parentId === null || $parentId === 'null' || $parentId === '') {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', $parentId);
            }
        } else {
            // По умолчанию - только корневые папки
            $query->whereNull('parent_id');
        }

        // Сортировка по позиции, затем по ID
        $query->orderBy('position', 'asc')->orderBy('id', 'asc');

        return FolderResource::collection($query->get());
    }

    /**
     * Создать новую папку
     * 
     * @OA\Post(
     *     path="/folders",
     *     tags={"Folders"},
     *     summary="Создать новую папку",
     *     description="Создаёт новую папку в медиа-менеджере",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Новая папка", description="Название папки"),
     *             @OA\Property(property="parent_id", type="integer", nullable=true, example=null, description="ID родительской папки"),
     *             @OA\Property(property="slug", type="string", nullable=true, example="novaya-papka", description="URL-slug (генерируется автоматически)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Папка успешно создана",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Folder"))
     *         )
     *     ),
     *     @OA\Response(response=422, description="Ошибка валидации")
     * )
     * 
     * @param StoreFolderRequest $request
     * @return AnonymousResourceCollection
     */
    public function store(StoreFolderRequest $request): AnonymousResourceCollection
    {
        try {
            $folder = Folder::create($request->validated());

            // Возвращаем все папки того же уровня для обновления списка
            $query = Folder::with('children')->orderBy('position', 'asc')->orderBy('id', 'asc');
            
            if ($folder->parent_id) {
                $query->where('parent_id', $folder->parent_id);
            } else {
                $query->whereNull('parent_id');
            }

            return FolderResource::collection($query->get());
        } catch (\Exception $e) {
            Log::error('Folder creation error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Показать конкретную папку
     * 
     * @OA\Get(
     *     path="/folders/{id}",
     *     tags={"Folders"},
     *     summary="Получить информацию о папке",
     *     description="Возвращает детальную информацию о конкретной папке",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID папки",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Успешный ответ",
     *         @OA\JsonContent(ref="#/components/schemas/Folder")
     *     ),
     *     @OA\Response(response=404, description="Папка не найдена")
     * )
     * 
     * @param string $id
     * @return FolderResource
     */
    public function show(string $id): FolderResource
    {
        $folder = Folder::with(['children', 'parent', 'files'])->findOrFail($id);
        return new FolderResource($folder);
    }

    /**
     * Обновить папку
     * 
     * @OA\Put(
     *     path="/folders/{id}",
     *     tags={"Folders"},
     *     summary="Обновить папку",
     *     description="Обновляет данные папки",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID папки",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Обновлённая папка"),
     *             @OA\Property(property="parent_id", type="integer", nullable=true, example=null),
     *             @OA\Property(property="position", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Папка успешно обновлена",
     *         @OA\JsonContent(ref="#/components/schemas/Folder")
     *     ),
     *     @OA\Response(response=404, description="Папка не найдена"),
     *     @OA\Response(response=422, description="Ошибка валидации")
     * )
     * 
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $folder = Folder::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'parent_id' => 'nullable|exists:folders,id',
            'position' => 'sometimes|integer|min:0'
        ]);

        try {
            $folder->update($validated);
            $folder->load(['children', 'parent']);

            return response()->json([
                'success' => true,
                'message' => 'Папка успешно обновлена',
                'data' => new FolderResource($folder)
            ]);
        } catch (\Exception $e) {
            Log::error('Folder update error', [
                'folder_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка обновления папки'
            ], 500);
        }
    }

    /**
     * Удалить папку
     * 
     * @OA\Delete(
     *     path="/folders/{id}",
     *     tags={"Folders"},
     *     summary="Удалить папку",
     *     description="Удаляет папку и все её содержимое (файлы и вложенные папки)",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID папки",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Папка успешно удалена",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Папка успешно удалена")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Папка не найдена"),
     *     @OA\Response(response=500, description="Ошибка сервера")
     * )
     * 
     * @param string $id
     * @return JsonResponse
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $folder = Folder::findOrFail($id);

            DB::beginTransaction();

            // Удаление папки (каскадно удалятся вложенные папки и файлы)
            $folder->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Папка успешно удалена'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Folder deletion error', [
                'folder_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка удаления папки'
            ], 500);
        }
    }

    /**
     * Получить дерево папок
     * 
     * @OA\Get(
     *     path="/folders/tree/all",
     *     tags={"Folders"},
     *     summary="Получить дерево всех папок",
     *     description="Возвращает иерархическую структуру всех папок в виде дерева",
     *     @OA\Response(
     *         response=200,
     *         description="Успешный ответ",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Folder"))
     *         )
     *     )
     * )
     * 
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function tree(Request $request): AnonymousResourceCollection
    {
        // Получаем все корневые папки с их дочерними элементами
        $folders = Folder::with('children')
            ->whereNull('parent_id')
            ->orderBy('position', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        return FolderResource::collection($folders);
    }

    /**
     * Обновить позиции папок (drag & drop)
     * 
     * @OA\Post(
     *     path="/folders/update-positions",
     *     tags={"Folders"},
     *     summary="Обновить позиции папок",
     *     description="Массовое обновление позиций папок для реализации drag & drop",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"folders"},
     *             @OA\Property(
     *                 property="folders",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="position", type="integer", example=1)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Позиции успешно обновлены",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Позиции папок успешно обновлены")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Ошибка валидации"),
     *     @OA\Response(response=500, description="Ошибка сервера")
     * )
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function updatePositions(Request $request): JsonResponse
    {
        $request->validate([
            'folders' => 'required|array|min:1',
            'folders.*.id' => 'required|exists:folders,id',
            'folders.*.position' => 'required|integer|min:0'
        ], [
            'folders.required' => 'Массив папок обязателен',
            'folders.array' => 'Папки должны быть переданы в виде массива',
            'folders.*.id.required' => 'ID папки обязателен',
            'folders.*.id.exists' => 'Папка с указанным ID не найдена',
            'folders.*.position.required' => 'Позиция обязательна',
            'folders.*.position.integer' => 'Позиция должна быть целым числом'
        ]);

        try {
            DB::beginTransaction();

            foreach ($request->folders as $folderData) {
                Folder::where('id', $folderData['id'])->update([
                    'position' => $folderData['position']
                ]);
            }

            DB::commit();

            Log::info('Folder positions updated', [
                'count' => count($request->folders),
                'folders' => $request->folders
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Позиции папок успешно обновлены'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Folder positions update error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка обновления позиций: ' . $e->getMessage()
            ], 500);
        }
    }
}
