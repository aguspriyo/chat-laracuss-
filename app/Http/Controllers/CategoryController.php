<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Discussion;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
  public function show($categorySlug)
  {
    //get category berdasarkan categorySlug
    //cek apakah data category diatas ada
    //jika category tidak ada maka return abort 404
    //buat query discussion, eager load user dan category, get category berdasarkan id category diatas
    //dipaginasi 10
    //lalu return viewnya dengan semua variable diatas


    $category = Category::where('slug', $categorySlug)->first();
    if (!$category) {
      return abort(404);
    }
    $discussions = Discussion::with(['user', 'category'])
      ->where('category_id', $category->id)
      ->orderBy('created_at', 'desc')
      ->paginate(10)
      ->withQueryString();

    return response()->view('pages.discussions.index', [
      'discussions' => $discussions,
      'categories' => Category::all(),
      'withCategory' => $category,
    ]);
  }
}
