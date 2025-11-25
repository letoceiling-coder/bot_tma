<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\LogsRequestParameters;
use App\Models\WheelSection;
use App\Models\WheelSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WheelSectionController extends Controller
{
    use LogsRequestParameters;
    public function index(Request $request): JsonResponse
    {
        // Логируем все параметры запроса
        $this->logRequestParameters($request, 'wheel-sections/index');
        
        $sections = WheelSection::orderBy('position')->get();
        $settings = WheelSetting::getSettings();
        
        return response()->json([
            'data' => $sections,
            'settings' => [
                'required' => $settings->required,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        // Логируем все параметры запроса
        $this->logRequestParameters($request, 'wheel-sections/store');
        
        $data = $this->validateSection($request);
        $data['position'] = $data['position'] ?? (WheelSection::max('position') ?? -1) + 1;

        $section = WheelSection::create($data);

        return response()->json([
            'message' => 'Сектор создан',
            'data' => $section,
        ], 201);
    }

    public function show(Request $request, WheelSection $wheelSection): JsonResponse
    {
        // Логируем все параметры запроса
        $this->logRequestParameters($request, 'wheel-sections/show');
        
        return response()->json(['data' => $wheelSection]);
    }

    public function update(Request $request, WheelSection $wheelSection): JsonResponse
    {
        // Логируем все параметры запроса
        $this->logRequestParameters($request, 'wheel-sections/update');
        
        $data = $this->validateSection($request, true);
        $wheelSection->update($data);

        return response()->json([
            'message' => 'Сектор обновлён',
            'data' => $wheelSection,
        ]);
    }

    public function destroy(Request $request, WheelSection $wheelSection): JsonResponse
    {
        // Логируем все параметры запроса
        $this->logRequestParameters($request, 'wheel-sections/destroy');
        
        $wheelSection->delete();

        return response()->json(null, 204);
    }

    public function sync(Request $request): JsonResponse
    {
        // Логируем все параметры запроса
        $this->logRequestParameters($request, 'wheel-sections/sync');
        
        $payload = $request->validate([
            'sections' => ['required', 'array', 'min:1'],
            'sections.*.id' => ['nullable', 'integer', 'exists:wheel_sections,id'],
            'sections.*.text' => ['required', 'string', 'max:255'],
            'sections.*.type' => ['required', 'string', 'max:50'],
            'sections.*.image' => ['nullable', 'string', 'max:500'],
            'sections.*.answer' => ['nullable', 'string'],
            'sections.*.position' => ['nullable', 'integer', 'min:0'],
            'sections.*.is_active' => ['nullable', 'boolean'],
            'sections.*.probability' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'settings' => ['nullable', 'array'],
            'settings.required' => ['nullable', 'boolean'],
        ]);

        $ids = [];
        foreach ($payload['sections'] as $index => $sectionData) {
            $data = [
                'text' => $sectionData['text'],
                'type' => $sectionData['type'],
                'image' => $sectionData['image'] ?? null,
                'answer' => $sectionData['answer'] ?? null,
                'position' => $sectionData['position'] ?? $index,
                'is_active' => $sectionData['is_active'] ?? true,
                'probability' => $sectionData['probability'] ?? 0,
            ];

            if (!empty($sectionData['id'])) {
                $section = WheelSection::find($sectionData['id']);
                if ($section) {
                    $section->update($data);
                    $ids[] = $section->id;
                    continue;
                }
            }

            $section = WheelSection::create($data);
            $ids[] = $section->id;
        }

        if (!empty($ids)) {
            WheelSection::whereNotIn('id', $ids)->delete();
        } else {
            WheelSection::query()->delete();
        }

        // Обновляем настройки колеса
        if (isset($payload['settings'])) {
            WheelSetting::updateSettings($payload['settings']);
        }

        $settings = WheelSetting::getSettings();

        return response()->json([
            'message' => 'Настройки колеса обновлены',
            'data' => WheelSection::orderBy('position')->get(),
            'settings' => [
                'required' => $settings->required,
            ],
        ]);
    }

    protected function validateSection(Request $request, bool $isUpdate = false): array
    {
        return $request->validate([
            'text' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:50'],
            'image' => ['nullable', 'string', 'max:500'],
            'answer' => ['nullable', 'string'],
            'position' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'probability' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);
    }
}

