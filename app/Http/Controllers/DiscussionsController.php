<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Discussion;
use App\Models\Answer;
use App\Http\Requests\Discussion\StoreRequest;
use App\Http\Requests\Discussion\UpdateRequest;
use Str;

class DiscussionsController extends Controller
{
  /**
   * Display a listing of the resource.
   */
  public function index(Request $request)
  {
    //load semua discussion
    //eager load relationshipnya / relasinya
    //apakah ada request data search
    //jika ada maka load discussion dengan kata kunci title dan content2 yg nilainya seperti nilai search
    //return page index beserta datanya
    //data yg di pass ke view adlah
    //discussion yg sudah disort dengan created_at menurun, pagination per 10/20 (sesuaikan sesuai keinginan)
    //data semua kategory

    $discussions = Discussion::with('user', 'category');

    if ($request->search) {
      $discussions->where('title', 'like', "%$request->search%")
        ->orWhere('content', 'like', "%$request->search%");

    }

    return response()->view('pages.discussions.index', [
      'discussions' => $discussions->orderBy('created_at', 'desc')
        ->paginate(5)->withQueryString(),
      'categories' => Category::all(),
      'search' => $request->search,
    ]);

  }

  /**
   * Show the form for creating a new resource.
   */
  public function create()
  {
    return response()->view('pages.discussions.form', [
      'categories' => Category::all(),
    ]);
  }

  /**
   * Store a newly created resource in storage.
   */
  public function store(StoreRequest $request)
  {
    //dapatkan dulu data dari form request yang sudah tervalidasi
    //get data category berdasarkan slug nya
    //daapatkan id dari category nya
    //masukkan user id ke array validated
    //tambahkan slug + timestamp berdasarkan title ke array validated
    //buat content preview berdasarkan content, caranya:
    //1.cek apakah content ini karakternya lebih dari 120
    //2. jika iya maka masukkan content tersebut ke content preview dengan '...'
    //3. jika tidak maka masukkan content tersebut ke content preview tanpa '...'
    //4. baru masukkan data detail question itu ke tabel discussion
    //5. jika berhasil maka buat notif berhasil, lalu redirect ke list discussions
    //6. jika tidak maka kembalikan error 500


    $validated = $request->validated();
    $categoryId = Category::where('slug', $validated['category_slug'])->first()->id;

    $validated['category_id'] = $categoryId;
    $validated['user_id'] = auth()->id();
    $validated['slug'] = Str::slug($validated['title']) . '-' . time();

    $stripContent = strip_tags($validated['content']);
    $isContentlong = strlen($stripContent) > 120;
    $validated['content_preview'] = $isContentlong ? (substr($stripContent, 0, 120) . '...')
      : $stripContent;

    $create = Discussion::create($validated);
    if ($create) {
      session()->flash('notif.success', 'Discussions created successfully!');
      return redirect()->route('discussions.index');
    }
    return abort(500);
  }

  /**
   * Display the specified resource.
   */
  public function show(string $slug)
  {
    //dapatkan discussion berdasarkan slug
    //cek apakah data discussion dengan slug tersebut tidak ada
    //jika tidak ada maka return page not found
    //get answer berdasarkan discussion id
    //sort berdasarkan created_at menurun
    //pagiante 5
    //get semua category
    //return response

    $discussion = Discussion::with(['user', 'category'])->where('slug', $slug)->first();
    if (!$discussion) {
      return abort(404);
    }

    $discussionAnswers = Answer::where('discussion_id', $discussion->id)
      ->orderBy('created_at', 'desc')
      ->paginate(5);

    $notLikeImage = url('assets/images/like.png');
    $likedImage = url('assets/images/liked.png');

    return response()->view('pages.discussions.show', [
      'discussion' => $discussion,
      'categories' => Category::all(),
      'likedImage' => $likedImage,
      'notLikeImage' => $notLikeImage,
      'discussionAnswers' => $discussionAnswers,
    ]);
  }

  /**
   * Show the form for editing the specified resource.
   */
  public function edit(string $slug)
  {
    //get data discussion berdasarkan slug
    //cek apakah data discussion dengan slug tersebut tidak ada
    //jika tidak ada maka return page not found
    //jika ada maka lanjut ke kodingan bawah
    //cek apakah discussion tersebut milik user yg sedang login
    //jika bukan return page not found
    //return view dengan discussion dan category


    $discussion = Discussion::with('category')->where('slug', $slug)->first();
    if (!$discussion) {
      return abort(404);
    }

    $isOwnedByUser = $discussion->user_id == auth()->id();
    if (!$isOwnedByUser) {
      return abort(404);

    }
    return response()->view('pages.discussions.form', [
      'discussion' => $discussion,
      'categories' => Category::all(),
    ]);
  }

  /**
   * Update the specified resource in storage.
   */
  public function update(UpdateRequest $request, string $slug)
  {
    //get data discussion berdasarkan slug
    //cek apakah data discussion dengan slug tersebut tidak ada
    //jika tidak ada maka return page not found
    //jika ada maka lanjut ke kodingan bawah
    //cek apakah discussion tersebut milik user yg sedang login
    //jika bukan return page not found
    //return view dengan discussion dan category
    //dapatkan dulu data dari form request yang sudah tervalidasi
    //get data category berdasarkan slug nya
    //daapatkan id dari category nya
    //masukkan user id ke array validated
    //buat content preview berdasarkan content, caranya:
    //1.cek apakah content ini karakternya lebih dari 120
    //2. jika iya maka masukkan content tersebut ke content preview dengan '...'
    //3. jika tidak maka masukkan content tersebut ke content preview tanpa '...'
    //4. baru update data detail question itu ke tabel discussion
    //5. jika berhasil maka buat notif berhasil, lalu redirect ke list discussions
    //6. jika tidak maka kembalikan error 500



    $discussion = Discussion::with('category')->where('slug', $slug)->first();
    if (!$discussion) {
      return abort(404);
    }

    $isOwnedByUser = $discussion->user_id == auth()->id();
    if (!$isOwnedByUser) {
      return abort(404);

    }

    $validated = $request->validated();
    $categoryId = Category::where('slug', $validated['category_slug'])->first()->id;

    $validated['category_id'] = $categoryId;
    $validated['user_id'] = auth()->id();

    $stripContent = strip_tags($validated['content']);
    $isContentlong = strlen($stripContent) > 120;
    $validated['content_preview'] = $isContentlong ? (substr($stripContent, 0, 120) . '...')
      : $stripContent;

    $update = $discussion->update($validated);
    if ($update) {
      session()->flash('notif.success', 'Discussions updated successfully!');
      return redirect()->route('discussions.show', $slug);
    }
    return abort(500);
  }

  /**
   * Remove the specified resource from storage.
   */
  public function destroy(string $slug)
  {
    ///get data discussion berdasarkan slug
    //cek apakah data discussion dengan slug tersebut tidak ada
    //jika tidak ada maka return page not found
    //jika ada maka lanjut ke kodingan bawah
    //cek apakah discussion tersebut milik user yg sedang login
    //jika bukan return page not found
    //delete record
    //jika berhsil maka return notif success dan redirect ke list discussion
    //jika tidak berhasil maka lanjut kodingan dibawahnya yakni error 500

    $discussion = Discussion::with('category')->where('slug', $slug)->first();
    if (!$discussion) {
      return abort(404);
    }

    $isOwnedByUser = $discussion->user_id == auth()->id();
    if (!$isOwnedByUser) {
      return abort(404);

    }
    $delete = $discussion->delete();

    if ($delete) {
      session()->flash('notif.success', 'Discussion deleted successfully');
      return redirect()->route('discussions.index');
    }
    return abort(500);
  }
}
