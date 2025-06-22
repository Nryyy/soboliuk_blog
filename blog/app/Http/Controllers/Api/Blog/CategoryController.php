<?php
namespace App\Http\Controllers\Api\Blog;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = BlogCategory::orderBy('created_at', 'desc')->get();
        
        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    public function show($id)
    {
        $category = BlogCategory::find($id);
        
        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Категорію не знайдено'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $category
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'slug' => 'nullable|string|max:255|unique:blog_categories,slug',
            'parent_id' => 'nullable|integer|exists:blog_categories,id'
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']);
            
            $originalSlug = $validated['slug'];
            $counter = 1;
            while (BlogCategory::where('slug', $validated['slug'])->exists()) {
                $validated['slug'] = $originalSlug . '-' . $counter;
                $counter++;
            }
        }

        if (isset($validated['parent_id']) && $validated['parent_id'] === 0) {
            $validated['parent_id'] = null;
        }

        $category = BlogCategory::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Категорію успішно створено',
            'data' => $category->fresh()
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $category = BlogCategory::find($id);
        
        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Категорію не знайдено'
            ], 404);
        }

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:500',
            'slug' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('blog_categories', 'slug')->ignore($category->id)
            ],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('blog_categories', 'id')->where(function ($query) use ($category) {
                    $query->where('id', '!=', $category->id);
                })
            ]
        ]);

        if (isset($validated['title']) && !isset($validated['slug'])) {
            $newSlug = Str::slug($validated['title']);
            if ($newSlug !== $category->slug) {
                $originalSlug = $newSlug;
                $counter = 1;
                while (BlogCategory::where('slug', $newSlug)->where('id', '!=', $category->id)->exists()) {
                    $newSlug = $originalSlug . '-' . $counter;
                    $counter++;
                }
                $validated['slug'] = $newSlug;
            }
        }

        if (array_key_exists('parent_id', $validated) && $validated['parent_id'] === 0) {
            $validated['parent_id'] = null;
        }

        $category->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Категорію успішно оновлено',
            'data' => $category->fresh()
        ]);
    }

    public function destroy($id)
    {
        $category = BlogCategory::find($id);
        
        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Категорію не знайдено'
            ], 404);
        }

        $hasChildren = BlogCategory::where('parent_id', $category->id)->exists();
        if ($hasChildren) {
            return response()->json([
                'success' => false,
                'message' => 'Неможливо видалити категорію, яка має підкатегорії'
            ], 422);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Категорію успішно видалено'
        ]);
    }
}