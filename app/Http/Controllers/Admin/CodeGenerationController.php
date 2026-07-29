<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CodeGeneratorService;
use App\Support\CodeGeneration\CodeDefinitionRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Jogosultsághoz kötött üzleti kódjavaslatot szolgáltat.
 */
final class CodeGenerationController extends Controller
{
    public function __construct(
        private readonly CodeDefinitionRegistry $registry,
        private readonly CodeGeneratorService $generator,
    ) {}

    public function __invoke(Request $request, string $type): JsonResponse
    {
        $context = $request->validate([
            'item_type' => ['nullable', 'string'],
        ]);
        $definition = $this->registry->resolve($type, $context);

        $this->authorize('create', $definition->modelClass);

        return response()->json([
            'code' => $this->generator->generate($type, $context),
            'generated' => true,
            'type' => $type,
        ]);
    }
}
