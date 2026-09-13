<?php

namespace App\Http\Controllers\Admin\Packages;

use App\Http\Controllers\Admin\DevHelpersContoller;
use App\Http\Controllers\Admin\Packages\UploadImageController;
use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use function PHPUnit\Framework\isJson;
use ZipArchive;

class SearchController extends Controller
{

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function searchHashtag(Request $request)
    {
        if ($request->ajax() && !empty($request->search)) {
            $output = "";

            $user = auth()->user();

            //var_dump($request->hashtags);

            $requestHashtags = $request->hashtags;

            // сортируем по релевантности: сначала хештеги, где искомая подстрока встречается
            // раньше в названии (в идеале — в самом начале), а не в порядке "как найдётся в БД"
            if (!empty($requestHashtags)) {
                $hashtags = DB::table('hashtags')
                    ->where('title', 'LIKE', '%' . $request->search . "%")
                    ->where('user_id', $user->id)
                    ->whereNotIn('id', array_keys($requestHashtags))
                    ->orderByRaw('LOCATE(?, title) ASC', [$request->search])
                    ->orderBy('title', 'asc')
                    ->get();
            } else {
                $hashtags = DB::table('hashtags')
                    ->where('title', 'LIKE', '%' . $request->search . "%")
                    ->where('user_id', $user->id)
                    ->orderByRaw('LOCATE(?, title) ASC', [$request->search])
                    ->orderBy('title', 'asc')
                    ->get();
            }

            if (count($hashtags)) {

                foreach ($hashtags as $key => $hashtag) {
                    $output .= '' .
                        '' . $hashtag->id . '' .
                        '' . $hashtag->title . '' .
                        '';
                }

                return response()->json(['status' => true, 'hashtags' => $hashtags]);
            }
        }

        return response()->json(['status' => false]);
    }

    public function searchSameHashtag(Request $request)
    {
        if ($request->ajax() && !empty($request->title)) {
            $foundHashtags = $this->searchTagByTitle($request->title);

            return response()->json(['status' => true, 'hashtags' => $foundHashtags]);
        }

        return response()->json(['status' => false]);
    }

    /**
     * @param $title
     * @param array $hashtags
     * @return \Illuminate\Support\Collection
     */
    public function searchTagByTitle($title, $hashtags = [])
    {
        $user = auth()->user();

        if (!empty($hashtags)) {
            $foundHashtags = DB::table('hashtags')
                ->where('title', $title)
                ->where('user_id', $user->id)
                //->whereNotIn('id', array_keys($hashtags))
                //->groupBy('parent_id')
                ->get();
        } else {
            $foundHashtags = DB::table('hashtags')
                ->where('title', $title)
                ->where('user_id', $user->id)
                //->groupBy('parent_id')
                ->get();
        }

//        dd($foundHashtags);
//        foreach ($foundHashtags as $key => $hashtag) {
//            dd($hashtag);
//        }

        return $foundHashtags;
    }


    /**
     * @param Request $request
     * @return Application|Factory|View|JsonResponse
     */
    public function searchPostsByHashtags(Request $request)
    {
        //TODO
        if ($request->ajax() && !empty($request->hashtags)) {
            $hashtags = $request->hashtags;
            $excludeHashtags = $request->excludeHashtags ?? [];
            session(['hashtags' => $hashtags]);
            session(['excludeHashtags' => $excludeHashtags]);
        } elseif ($request->session()->has('hashtags')) {
            $hashtags = session('hashtags');
            $excludeHashtags = session('excludeHashtags', []);
        }

        if (!empty($hashtags)) {

            $user = auth()->user();

            $hashtagsIds = array_keys($hashtags);
            $excludeHashtagsIds = !empty($excludeHashtags) ? array_keys($excludeHashtags) : [];

            $posts = $this->buildHashtagFilteredPostsQuery($hashtagsIds, $excludeHashtagsIds, $user->id)
                ->with('hashtags')
                ->latest()
                ->paginate(20);

//            $posts = Post::whereHas('hashtags', function($q) use ($hashtagsIds) {
//                //$q->select(['id', 'title', 'parent_id', 'user_id', 'associated_hashtags']);
//                $q->whereIn('hashtags.id', $hashtagsIds);
//            },
//            )->with([
//                    'hashtags' => function($q) use ($hashtagsIds) {
//                        //$q->select(['id', 'title', 'parent_id', 'user_id', 'associated_hashtags']);
//                        //$q->whereIn('hashtags.id', $hashtags);
//                    },
//                ])
//                ->where('user_id', $user->id)
//                ->latest()
//                ->paginate(20);

            foreach ($posts as $post) {
                if (!empty($post->small_image) && isJson($post->small_image)) {
                    $smallImage = json_decode($post->small_image, true);
                    $post['cover_image'] = $smallImage['path'];
                } elseif (!empty($post->medium_image) && isJson($post->medium_image)) {
                    $mediumImage = json_decode($post->medium_image, true);
                    $post['cover_image'] = $mediumImage['path'];
                } elseif (!empty($post->original_image) && isJson($post->original_image)) {
                    $originalImage = json_decode($post->original_image, true);
                    $post['cover_image'] = $originalImage['path'];
                }
            }

            if ($request->ajax()) {
                return view('admin.posts.parts.post_items', [
                    'posts' => $posts,
                ]);
            }

            return view('admin.posts.parts.post_items', [
                'posts' => $posts,
            ]);
        }

        return response()->json(['status' => false]);
    }

    /**
     * Общее для searchPostsByHashtags()/exportImagesByHashtags() построение запроса
     * постов по выбранным/исключённым хештегам.
     *
     * @param array $hashtagsIds
     * @param array $excludeHashtagsIds
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function buildHashtagFilteredPostsQuery(array $hashtagsIds, array $excludeHashtagsIds, int $userId)
    {
        return Post::where(function ($query) use ($hashtagsIds) {
            foreach ($hashtagsIds as $hashtagId) {
                $query->whereHas('hashtags', function ($q) use ($hashtagId) {
                    $q->where('hashtags.id', $hashtagId);
                });
            }
        })
            ->when(!empty($excludeHashtagsIds), function ($query) use ($excludeHashtagsIds) {
                $query->whereDoesntHave('hashtags', function ($q) use ($excludeHashtagsIds) {
                    $q->whereIn('hashtags.id', $excludeHashtagsIds);
                });
            })
            ->where('user_id', $userId);
    }

    /**
     * Скачать архивом (zip) оригинальные изображения всех постов, найденных
     * текущим активным поиском по хештегам (кнопка "Экспорт изображений" на
     * странице /admin_panel/posts). Фильтр берётся из той же сессии, что и
     * searchPostsByHashtags() — то есть архив собирается ровно из того, что
     * сейчас показано на экране.
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\RedirectResponse
     */
    public function exportImagesByHashtags(Request $request)
    {
        $hashtags = session('hashtags');
        $excludeHashtags = session('excludeHashtags', []);

        if (empty($hashtags)) {
            return redirect()->route('posts.index')->with('errors', 'Сначала выполните поиск по хештегам — экспортируются картинки из результатов поиска.');
        }

        $user = auth()->user();
        $hashtagsIds = array_keys($hashtags);
        $excludeHashtagsIds = !empty($excludeHashtags) ? array_keys($excludeHashtags) : [];

        $posts = $this->buildHashtagFilteredPostsQuery($hashtagsIds, $excludeHashtagsIds, $user->id)
            ->get(['id', 'original_image']);

        if ($posts->isEmpty()) {
            return redirect()->route('posts.index')->with('errors', 'По текущему поиску не найдено ни одного поста.');
        }

        // собираем оригинальные изображения всех найденных постов, без повторов внутри архива
        $filesToZip = []; // entryName => absolutePath
        foreach ($posts as $post) {
            $images = UploadImageController::normalizeImages($post->original_image);

            foreach ($images as $name => $image) {
                if (empty($image['path']) || empty($image['extension'])) {
                    continue;
                }

                $absolutePath = Storage::disk('local')->path($image['path']);
                if (!file_exists($absolutePath)) {
                    continue;
                }

                $entryName = $name . '.' . $image['extension'];
                $i = 1;
                while (isset($filesToZip[$entryName])) {
                    $entryName = $name . '-' . $i . '.' . $image['extension'];
                    $i++;
                }

                $filesToZip[$entryName] = $absolutePath;
            }
        }

        if (empty($filesToZip)) {
            return redirect()->route('posts.index')->with('errors', 'У найденных постов нет ни одного изображения на диске.');
        }

        $filename = $this->sanitizeExportFilename($request->query('filename'));

        $userId = $user->id;
        $tempDir = 'temp_directory/' . $userId;
        Storage::disk('local')->makeDirectory($tempDir);
        $tempZipPath = Storage::disk('local')->path($tempDir . '/' . $filename . '-' . time() . '.zip');

        $zip = new ZipArchive();
        if ($zip->open($tempZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return redirect()->route('posts.index')->with('errors', 'Не удалось создать архив.');
        }

        foreach ($filesToZip as $entryName => $absolutePath) {
            $zip->addFile($absolutePath, $entryName);
        }

        $zip->close();

        return response()->download($tempZipPath, $filename . '.zip')->deleteFileAfterSend(true);
    }

    /**
     * Приводит имя архива, введённое пользователем, к безопасному виду для
     * заголовка Content-Disposition и файловой системы.
     *
     * @param string|null $rawFilename
     * @return string
     */
    private function sanitizeExportFilename(?string $rawFilename): string
    {
        $filename = trim((string) $rawFilename);
        // оставляем буквы (в т.ч. кириллицу), цифры, пробел, дефис, подчёркивание
        $filename = preg_replace('/[^\p{L}\p{N} _\-]+/u', '', $filename);
        $filename = trim(preg_replace('/\s+/', ' ', $filename));
        $filename = mb_substr($filename, 0, 150);

        if ($filename === '') {
            $filename = 'изображения-' . now()->format('Y-m-d');
        }

        return $filename;
    }

}
