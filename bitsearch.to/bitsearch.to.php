<?php
class bitsearch implements ISite, ISearch {
	const SITE = "https://bitsearch.to";
    private $url;

    public function __construct($url = null, $username = null, $password = null, $meta = null) {
        $this->url = $url;
    }

    static function UnitSize($unit) {
        switch (strtoupper($unit)) {
        case "KB": return 1000;
        case "MB": return 1000000;
        case "GB": return 1000000000;
        case "TB": return 1000000000000;
        default: return 1;
        }
    }

    public function Search($keyword, $limit, $category) {
        $page = 1;
        $keyword = urlencode($keyword);

        $ajax = new Ajax();
        $found = array();
        $success = function ($_1, $_2, $_3, $body, $_4) use(&$page, &$found, &$limit) {
            preg_match_all(
                "`".
                    "<a[^>]*href=\"/torrent/[^\"]+\"[^>]*>\\s*(?P<name>[^<]+?)\\s*</a>.*?".
                    "(?P<size>[\\d.]+ [KMGT]?B).*?".
                    "fa-calendar.*?<span>(?P<date>[^<]+)</span>.*?".
                    "text-green.*?font-medium\">(?P<seeds>\\d+)</span>.*?".
                    "text-red.*?font-medium\">(?P<leechers>\\d+)</span>.*?".
                    "magnet:\\?xt(?:&#x3D;|=)urn:btih:(?P<hash>[A-Fa-f0-9]+)".
                "`siU",
                $body,
                $result
            );

            if (!$result || ($len = count($result["name"])) == 0 ) {
                $page = false;
                return;
            }

            for ($i = 0 ; $i < $len ; ++$i) {
                $tlink = new SearchLink;

                $tlink->src           = "bitsearch.to";
                $tlink->name          = html_entity_decode(trim($result["name"][$i]), ENT_QUOTES, 'UTF-8');

                $sizeparts = explode(" ", trim($result["size"][$i]));
                $tlink->size          = ($sizeparts[0] + 0) * self::UnitSize($sizeparts[1]);

                $datestr = trim($result["date"][$i]);
                $tlink->time          = DateTime::createFromFormat("n/j/Y", $datestr);
                if (!$tlink->time) {
                    $tlink->time = new DateTime();
                }

                $hash = $result["hash"][$i];
                $tlink->link          = bitsearch::SITE . "/torrent/" . strtolower($hash);
                $tlink->seeds         = $result["seeds"][$i] + 0;
                $tlink->peers         = $result["leechers"][$i] + 0;
                $tlink->category      = "";
                $tlink->enclosure_url = bitsearch::SITE . "/download/torrent/" . $hash . "?title=" . urlencode($tlink->name);

                $found []= $tlink;

                if (count($found) >= $limit) {
                    $page = false;
                    return;
                }
            }

            ++$page;
        };

        while ($page !== false && count($found) < $limit) {
            if (!$ajax->request(Array("url" => bitsearch::SITE."/search?q=$keyword&page=$page"), $success)) {
                break;
            }
        }

        return $found;
    }
}
?>
