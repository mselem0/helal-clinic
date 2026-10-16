<?php
namespace App\Http\Controllers;
use App\Helpers\SeoHelper;
use App\Models\{Service, Article};
class SitemapController extends Controller
{
    public function index() {
        abort_unless(in_array(SeoHelper::getSetting('sitemap_enabled'), ['1',1,true,'true'], true),404);
        $xml=new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>');
        $urls=[route('home'),route('services'),route('blog.index')];
        foreach(Service::where('is_active',true)->get() as $service) { $urls[]=route('services.show',$service->slug); }
        foreach(Article::where('is_published',true)->get() as $article) { $urls[]=route('blog.show',$article->slug); }
        foreach($urls as $url) { $node=$xml->addChild('url');$node->addChild('loc',htmlspecialchars($url,ENT_XML1)); }
        return response($xml->asXML(),200,['Content-Type'=>'application/xml']);
    }
}
