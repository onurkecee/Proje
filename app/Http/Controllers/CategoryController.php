<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Category::class);

        $categories = $request->user()->categories()
            ->orderBy('type')
            ->orderBy('name')
            ->get()
            ->map(
                /**
                 * @return array{id: int, name: string, type: string, isActive: bool}
                 */
                fn (Category $category): array => [
                    'id' => (int) $category->id,
                    'name' => $category->name,
                    'type' => $category->type->value,
                    'isActive' => $category->is_active,
                ],
            )
            ->values();

        return Inertia::render('categories/Index', [
            'categories' => $categories,
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Category::class);

        return Inertia::render('categories/Form', [
            'category' => null,
        ]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        Gate::authorize('create', Category::class);

        $request->user()->categories()->create([
            ...$request->validated(),
            'is_active' => true,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kategori oluşturuldu.']);

        return to_route('categories.index');
    }

    public function edit(Request $request, int $category): Response
    {
        $category = $request->user()->categories()->findOrFail($category);
        Gate::authorize('update', $category);

        return Inertia::render('categories/Form', [
            'category' => [
                'id' => (int) $category->id,
                'name' => $category->name,
                'type' => $category->type->value,
                'isActive' => $category->is_active,
                'canDelete' => ! $category->transactions()->exists(),
            ],
        ]);
    }

    public function update(CategoryRequest $request, int $category): RedirectResponse
    {
        $category = $request->user()->categories()->findOrFail($category);
        Gate::authorize('update', $category);

        $category->update([
            ...$request->validated(),
            'is_active' => $request->boolean('is_active'),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kategori güncellendi.']);

        return to_route('categories.index');
    }

    public function destroy(Request $request, int $category): RedirectResponse
    {
        $category = $request->user()->categories()->findOrFail($category);
        Gate::authorize('delete', $category);
        $category->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kategori silindi.']);

        return to_route('categories.index');
    }
}
