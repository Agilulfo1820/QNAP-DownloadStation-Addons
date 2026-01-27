<?php
class nyaa implements ISite, ISearch {
	const SITE = "https://nyaa.si";
    private $url;

    public function __construct($url = null, $username = null, $password = null, $meta = null) {
        $this->url = $url;
    }

    static function UnitSize($unit) {
        switch (strtoupper($unit)) {
        case "KIB": return 1024;
        case "MIB": return 1048576;
        case "GIB": return 1073741824;
        case "TIB": return 1099511627776;
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
                    "<tr class=\"(?:default|success|danger|warning)\">.*".
                    "<a href=\"/\\?c=[^\"]+\"[^>]*>.*?</a>.*".
                    "<a href=\"(?P<link>/view/\\d+)\"[^>]*>(?P<name>[^<]+)</a>.*".
                    "<a href=\"(?P<torrent>/download/\\d+\\.torrent)\">.*</a>.*".
                    "<a href=\"(?P<magnet>magnet:[^\"]+)\">.*</a>.*".
                    "<td class=\"text-center\">(?P<size>[\\d.]+ [a-zA-Z]+)</td>.*".
                    "<td class=\"text-center\"[^>]*>(?P<time>[^<]+)</td>.*".
                    "<td class=\"text-center\">(?P<seeds>\\d+)</td>.*".
                    "<td class=\"text-center\">(?P<leechers>\\d+)</td>.*".
                    "</tr>".
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

                $tlink->src           = "nyaa.si";
                $tlink->link          = nyaa::SITE . $result["link"][$i];
                $tlink->name          = html_entity_decode(trim($result["name"][$i]), ENT_QUOTES, 'UTF-8');

                $sizeparts = explode(" ", trim($result["size"][$i]));
                $tlink->size          = ($sizeparts[0] + 0) * self::UnitSize($sizeparts[1]);

                $tlink->time          = DateTime::createFromFormat("Y-m-d H:i", trim($result["time"][$i]));
                $tlink->seeds         = $result["seeds"][$i] + 0;
                $tlink->peers         = $result["leechers"][$i] + 0;
                $tlink->category      = "Anime";
                $tlink->enclosure_url = html_entity_decode($result["magnet"][$i], ENT_QUOTES, 'UTF-8');

                $found []= $tlink;

                if (count($found) >= $limit) {
                    $page = false;
                    return;
                }
            }

            ++$page;
        };

        while ($page !== false && count($found) < $limit) {
            if (!$ajax->request(Array("url" => nyaa::SITE."/?f=0&c=0_0&q=$keyword&p=$page"), $success)) {
                break;
            }
        }

        return $found;
    }
}
?>
