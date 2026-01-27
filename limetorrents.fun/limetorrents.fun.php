<?php
class limetorrents implements ISite, ISearch {
	const SITE = "https://www.limetorrents.fun";
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
                    "<tr.*".
                    "<a href=\"(?P<link>http://itorrents[^\"]+)\"[^>]*>[^<]*</a>".
                    "<a[^>]*>(?P<name>[^<]*)</a>.*".
                    "<td class=\"tdnormal\">(?P<time>[^<-]+)- in (?P<category>[^<]+)</a></td>.*".
                    "<td class=\"tdnormal\">(?P<size>[\\d.]+) (?P<unit>[A-Z]+)</td>.*".
                    "<td class=\"tdseed\">(?P<seeds>[\\d,]+)</td>.*".
                    "<td class=\"tdleech\">(?P<leechers>[\\d,]+)</td>.*".
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
                $date = new DateTime();
                $time = trim($result["time"][$i]);
                $time = preg_replace("/s? ago/", "", $time);
                $seeds = preg_replace("/,/", "", $result["seeds"][$i]);
                $peers = preg_replace("/,/", "", $result["leechers"][$i]);

                $tlink->src           = "limetorrents.fun";
                $tlink->link          = $result["link"][$i];
                $tlink->name          = strip_tags(trim($result["name"][$i]));
                $tlink->size          = ($result["size"][$i] + 0) * self::UnitSize($result["unit"][$i]);

                if (preg_match("/Year/i", $time)) {
                    $tlink->time = date_sub($date, date_interval_create_from_date_string("1 year"));
                } elseif (preg_match("/Last Month/i", $time)) {
                    $tlink->time = date_sub($date, date_interval_create_from_date_string("1 month"));
                } else {
                    $tlink->time = date_sub($date, date_interval_create_from_date_string($time));
                }

                $tlink->seeds         = $seeds + 0;
                $tlink->peers         = $peers + 0;
                $tlink->category      = trim($result["category"][$i]);
                $tlink->enclosure_url = $tlink->link;

                $found []= $tlink;

                if (count($found) >= $limit) {
                    $page = false;
                    return;
                }
            }

            ++$page;
        };

        while ($page !== false && count($found) < $limit) {
            if (!$ajax->request(Array("url" => limetorrents::SITE."/search/all/$keyword/$page/"), $success)) {
                break;
            }
        }

        return $found;
    }
}
?>
