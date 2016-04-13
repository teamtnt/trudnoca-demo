<?php namespace App\Console\Commands;

use Illuminate\Console\Command;
use TeamTNT\TNTSearch\TNTSearch;
use Config;
use Goutte\Client;
use App\Url;
use App\Article;

class IndexDocumentation extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'index';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Index all documentation with TNTSearch';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        //$this->scrapePHPUnitDe();

        $tnt = new TNTSearch;

        $config = [
            'driver'    => 'sqlite',
            'database'  => base_path().'/database/database.sqlite',
            'username'  => '',
            'password'  => '',
            'storage'   => storage_path()
        ];

        $tnt->loadConfig($config);
        $indexer = $tnt->createIndex('trudnoca.index');
        $indexer->query('SELECT * FROM articles;');
        $indexer->setLanguage('croatian');
        $indexer->run();
    }

    public function scrapePHPUnitDe()
    {
        $client = new Client();
        do{
            $link = Url::where('scraped', 0)->first();

            $crawler = $client->request('GET', $link->url);

            $crawler->filter('a')->each(function ($node) {
                preg_match("/www.trudnoca.hr\/(.*)/",$node->attr('href'), $matches);
                if(count($matches) > 1) {
                    try {
                        $url = new Url;
                        $url->url = $node->attr('href');
                        $url->scraped = 0;
                        $url->save();
                    } catch (\Exception $e) {
                        //echo $e->getMessage() . "\n";
                    }
                }
            });
            $article = new Article;

            $crawler->filter('body.single-post h1')->each(function($node) use (&$article) {
                $article->title = $node->text();
            });

            $crawler->filter('.content .article_wrap .text-control')->each(function($node) use (&$article) {
                $article->article = $node->html();
            });

            $article->url = $link->url;

            if($article->title) {
                $article->save();
                echo $article->title . "\n";
            }

            $link->scraped = 1;
            $link->save();

        } while($link);
    }
}
