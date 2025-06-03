<?php

namespace App\Http\Controllers\Blog\Admin;

//use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class CategoryController extends BaseController
{
    public function index()
    {
        //dd(__METHOD__);
        $paginator = BlogCategory::paginate(5);

        return view('blog.admin.categories.index', compact('paginator'));
    }

    public function create()
    {
        //dd(__METHOD__);
        //
    }

    public function store(Request $request)
    {
        //dd(__METHOD__);
        //
    }

    public function show(string $id)
    {
        //dd(__METHOD__);
        //
    }

    public function edit(string $id)
    {
        //dd(__METHOD__);
        $item = BlogCategory::findOrFail($id);
        $categoryList = BlogCategory::all();

        return view('blog.admin.categories.edit', compact('item', 'categoryList'));
    }

    public function update(Request $request, string $id)
    {
        //dd(__METHOD__);
        $item = BlogCategory::find($id);
        if (empty($item)) { 
            return back() 
                ->withErrors(['msg' => "Запис id=[{$id}] не знайдено"]) 
                ->withInput(); 
        }

        $data = $request->all(); 
        if (empty($data['slug'])) { 
            $data['slug'] = Str::slug($data['title']);
        }

        $result = $item->update($data);

        if ($result) {
            return redirect()
                ->route('blog.admin.categories.edit', $item->id)
                ->with(['success' => 'Успішно збережено']);
        } else {
            return back()
                ->with(['msg' => 'Помилка збереження'])
                ->withInput();
        }
    }

    public function destroy(string $id)
    {
        //dd(__METHOD__);
        //
    }
}