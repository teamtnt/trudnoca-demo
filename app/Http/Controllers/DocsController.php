<?php namespace App\Http\Controllers;

use App\Documentation;
use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Http\Request;
use TeamTNT\TNTSearch\TNTSearch;
use TeamTNT\TNTSearch\Stemmer\CroatianStemmer;
use App\Article;

class DocsController extends Controller {

    /**
     * The documentation repository.
     *
     * @var Documentation
     */
    protected $docs;

    /**
     * Create a new controller instance.
     *
     * @param  Documentation  $docs
     * @return void
     */
    public function __construct(Documentation $docs, TNTSearch $tnt)
    {
        $this->docs = $docs;
        $this->tnt  = $tnt;
    }

    /**
     * Show a documentation page.
     *
     * @return Response
     */
    public function show($page = null)
    {
        $article = Article::where('id', $page)->first();

        if (is_null($article)) {
        
            return view('docs', [
                'title' => "Pretrazi trudnoca.hr",
                'index' => "",
                'content' => "",
            ]);
        }

        return view('docs', [
            'title' => $article->title,
            'index' => "",
            'content' => $article->article,
        ]);
    }

    public function search(Request $request)
    {
        $this->tnt->loadConfig([
            "storage"   => storage_path(),
        ]);

        $this->tnt->selectIndex("trudnoca.index");
        $this->tnt->asYouType = true;

        $results = $this->tnt->search($request->get('query'), $request->get('params')['hitsPerPage']);
        return $this->processResults($results, $request);
    }

    public function processResults($res, $request)
    {
        $data = ['hits' => [], 'nbHits' => count($res)];
        $query = CroatianStemmer::stem(trim($request->get('query')));

        if (count($res['ids']) == 0) {
            return response()->json($data);
        }
        $order = "";
        foreach ($res['ids'] as $index => $id) {
            $order .= "WHEN $id THEN $index ";
        }

        $articles = Article::whereIn('id', $res['ids'])
                    ->orderByRaw("CASE id $order END")
                    ->get();

        foreach ($articles as $article) {
            $title = $article->title;

            $relevant = $this->tnt->snippet($query, strip_tags($article->article));

            $data['hits'][] = [
                'link' => $article->id,
                '_highlightResult' => [
                    'h1' => [
                        'value' => $title,
                    ],
                    'content' => [
                        'value' => $relevant,
                    ]
                ]
            ];
        }

        return response()->json($data);
    }

}