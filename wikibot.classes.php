<?php
    /*
     * Copyright (C) 2015 Jacobi Carter
     *
     * This file is part of ClueBot III.
     *
     * ClueBot III is free software: you can redistribute it and/or modify
     * it under the terms of the GNU General Public License as published by
     * the Free Software Foundation, either version 2 of the License, or
     * (at your option) any later version.
     *
     * ClueBot III is distributed in the hope that it will be useful,
     * but WITHOUT ANY WARRANTY; without even the implied warranty of
     * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
     * GNU General Public License for more details.
     *
     * You should have received a copy of the GNU General Public License
     * along with ClueBot III.  If not, see <http://www.gnu.org/licenses/>.
     */

    /**
     * This class is designed to provide a simplified interface to cURL which maintains cookies.
     *
     * @author Cobi
     **/
    class http
    {
        private $ch;
        private $uid;
        public $postfollowredirs;
        public $getfollowredirs;

        /**
         * Our constructor function.  This just does basic cURL initialization.
         **/
        public function __construct()
        {
            global $proxyhost, $proxyport, $proxytype;
            $this->ch = curl_init();
            $this->uid = dechex(rand(0, 99999999));
            curl_setopt($this->ch, CURLOPT_COOKIEJAR, '/tmp/cluewikibot.cookies.'.$this->uid.'.dat');
            curl_setopt($this->ch, CURLOPT_COOKIEFILE, '/tmp/cluewikibot.cookies.'.$this->uid.'.dat');
            curl_setopt($this->ch, CURLOPT_MAXCONNECTS, 100);
            curl_setopt($this->ch, CURLOPT_USERAGENT, 'ClueBot/1.1');
            if (isset($proxyhost) and isset($proxyport) and ($proxyport != null) and ($proxyhost != null)) {
                curl_setopt($this->ch, CURLOPT_PROXYTYPE, isset($proxytype) ? $proxytype : CURLPROXY_HTTP);
                curl_setopt($this->ch, CURLOPT_PROXY, $proxyhost);
                curl_setopt($this->ch, CURLOPT_PROXYPORT, $proxyport);
            }
            $this->postfollowredirs = 0;
            $this->getfollowredirs = 1;
        }

        /**
         * Post to a URL.
         *
         * @param $url The URL to post to
         * @param $data The post-data to post, should be an array of key => value pairs
         *
         * @return Data retrieved from the POST request
         **/
        public function post($url, $data)
        {
            global $logger;
            $time = microtime(1);
            curl_setopt($this->ch, CURLOPT_URL, $url);
            curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, $this->postfollowredirs);
            curl_setopt($this->ch, CURLOPT_MAXREDIRS, 10);
            curl_setopt($this->ch, CURLOPT_HEADER, 0);
            curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($this->ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($this->ch, CURLOPT_POST, 1);
            curl_setopt($this->ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($this->ch, CURLOPT_HTTPHEADER, array('Expect:'));
            $data = curl_exec($this->ch);

            $logger->addDebug('POST: '.$url.' ('.(microtime(1) - $time).' s) ('.strlen($data).' b)');

            return $data;
        }

        /**
         * Get a URL.
         *
         * @param $url The URL to get
         *
         * @return Data retrieved from the GET request
         **/
        public function get($url)
        {
            global $logger;
            $time = microtime(1);
            curl_setopt($this->ch, CURLOPT_URL, $url);
            curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, $this->getfollowredirs);
            curl_setopt($this->ch, CURLOPT_MAXREDIRS, 10);
            curl_setopt($this->ch, CURLOPT_HEADER, 0);
            curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($this->ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($this->ch, CURLOPT_HTTPGET, 1);
            $data = curl_exec($this->ch);

            $logger->addDebug('GET: '.$url.' ('.(microtime(1) - $time).' s) ('.strlen($data).' b)');

            return $data;
        }

        /**
         * Our destructor.  Cleans up cURL and unlinks temporary files.
         **/
        public function __destruct()
        {
            curl_close($this->ch);
        }
    }

    /**
     * This class is a deprecated wrapper class which allows legacy code written for Wikipedia's query.php API to still work with wikipediaapi::.
     **/
    class wikipediaquery
    {
        private $http;
        private $api;
        public $queryurl = 'https://en.wikipedia.org/w/query.php';

        /**
         * This is our constructor.
         **/
        public function __construct()
        {
            global $__wp__http;
            if (!isset($__wp__http)) {
                $__wp__http = new http();
            }
            $this->http = &$__wp__http;
            $this->api = new wikipediaapi();
        }

        /**
         * Reinitializes the queryurl.
         *
         * @private
         **/
        private function checkurl()
        {
            $this->api->apiurl = str_replace('query.php', 'api.php', $this->queryurl);
        }

        /**
         * Gets the content of a page.
         *
         * @param $page The wikipedia page to fetch
         *
         * @return The wikitext for the page
         **/
        public function getpage($page)
        {
            $this->checkurl();
            $ret = $this->api->revisions($page, 1, 'older', true, null, true, false, false, false);

            return $ret[0]['*'];
        }

        /**
         * Gets the page id for a page.
         *
         * @param $page The wikipedia page to get the id for
         *
         * @return The page id of the page
         **/
        public function getpageid($page)
        {
            $this->checkurl();
            $ret = $this->api->revisions($page, 1, 'older', false, null, true, false, false, false);

            return $ret['pageid'];
        }

        /**
         * Gets the number of contributions a user has.
         *
         * @param $user The username for which to get the edit count
         *
         * @return The number of contributions the user has
         **/
        public function contribcount($user)
        {
            $this->checkurl();
            $ret = $this->api->users($user, 1, null, true);
            if ($ret !== false) {
                return $ret[0]['editcount'];
            }

            return false;
        }
    }

    /**
     * This class is for interacting with Wikipedia's api.php API.
     **/
    class wikipediaapi
    {
        private $http;
        private $edittoken;
        private $tokencache;
        private $user;
        private $pass;
        public $apiurl = 'https://en.wikipedia.org/w/api.php';

        /**
         * This is our constructor.
         **/
        public function __construct()
        {
            global $__wp__http;
            if (!isset($__wp__http)) {
                $__wp__http = new http();
            }
            $this->http = &$__wp__http;
        }

        /**
         * This function takes a username and password and logs you into wikipedia.
         *
         * @param $user Username to login as
         * @param $pass Password that corrisponds to the username
         **/
        public function login($user, $pass)
        {
            global $logger;
            $this->user = $user;
            $this->pass = $pass;
            $x = $this->http->post($this->apiurl.'?action=login&format=php', array('lgname' => $user, 'lgpassword' => $pass));
            $logger->addDebug($x);
            $x = unserialize($x);
            if ($x['login']['result'] == 'Success') {
                return true;
            }
            if ($x['login']['result'] == 'NeedToken') {
                $x = $this->http->post($this->apiurl.'?action=login&format=php', array('lgname' => $user, 'lgpassword' => $pass, 'lgtoken' => $x['login']['token']));
                $logger->addDebug($x);
                $x = unserialize($x);
                if ($x['login']['result'] == 'Success') {
                    return true;
                }
            }

            return false;
        }

        /**
         * This function returns the edit token.
         *
         * @return Edit token
         **/
        public function getedittoken()
        {
            $tokens = $this->gettokens('Main Page');
            if ($tokens['edittoken'] == '') {
                $tokens = $this->gettokens('Main Page', true);
            }
            $this->edittoken = $tokens['edittoken'];

            return $tokens['edittoken'];
        }

        /**
         * This function returns the various tokens for a certain page.
         *
         * @param $title Page to get the tokens for
         * @param $flush Optional - internal use only.  Flushes the token cache
         *
         * @return An associative array of tokens for the page
         **/
        public function gettokens($title, $flush = false)
        {
            if (!is_array($this->tokencache)) {
                $this->tokencache = array();
            }
            foreach ($this->tokencache as $t => $data) {
                if (time() - $data['timestamp'] > 6 * 60 * 60) {
                    unset($this->tokencache[$t]);
                }
            }
            if (isset($this->tokencache[$title]) && (!$flush)) {
                return $this->tokencache[$title]['tokens'];
            } else {
                $tokens = array();
                $x = $this->http->get($this->apiurl.'?action=query&rawcontinue=1&format=php&prop=info&intoken=edit|delete|protect|move|block|unblock|email&titles='.urlencode($title));
                $x = unserialize($x);
                foreach ($x['query']['pages'] as $y) {
                    $tokens['edittoken'] = $y['edittoken'];
                    if (array_key_exists('deletetoken', $y)) {
                        $tokens['deletetoken'] = $y['deletetoken'];
                    }
                    if (array_key_exists('protecttoken', $y)) {
                        $tokens['protecttoken'] = $y['protecttoken'];
                    }
                    if (array_key_exists('movetoken', $y)) {
                        $tokens['movetoken'] = $y['movetoken'];
                    }
                    if (array_key_exists('blocktoken', $y)) {
                        $tokens['blocktoken'] = $y['blocktoken'];
                    }
                    if (array_key_exists('unblocktoken', $y)) {
                        $tokens['unblocktoken'] = $y['unblocktoken'];
                    }
                    if (array_key_exists('emailtoken', $y)) {
                        $tokens['emailtoken'] = $y['emailtoken'];
                    }
                    $this->tokencache[$title] = array(
                            'timestamp' => time(),
                            'tokens' => $tokens,
                                     );

                    return $tokens;
                }
            }
        }

        /**
         * This function returns the recent changes for the wiki.
         *
         * @param $count The number of items to return. (Default 10)
         * @param $namespace The namespace ID to filter items on. Null for no filtering. (Default null)
         * @param $dir The direction to pull items.  "older" or "newer".  (Default 'older')
         * @param $ts The timestamp to start at.  Null for the beginning/end (depending on direction).  (Default null)
         *
         * @return Associative array of recent changes metadata
         **/
        public function recentchanges($count = 10, $namespace = null, $dir = 'older', $ts = null)
        {
            $append = '';
            if ($ts !== null) {
                $append .= '&rcstart='.urlencode($ts);
            }
            $append .= '&rcdir='.urlencode($dir);
            if ($namespace !== null) {
                $append .= '&rcnamespace='.urlencode($namespace);
            }
            $x = $this->http->get($this->apiurl.'?action=query&rawcontinue=1&list=recentchanges&rcprop=user|comment|flags|timestamp|title|ids|sizes&format=php&rclimit='.$count.$append);
            $x = unserialize($x);

            return $x['query']['recentchanges'];
        }

        /**
         * This function returns search results from Wikipedia's internal search engine.
         *
         * @param $search The query string to search for
         * @param $limit The number of results to return. (Default 10)
         * @param $offset The number to start at.  (Default 0)
         * @param $namespace The namespace ID to filter by.  Null means no filtering.  (Default 0)
         * @param $what What to search, 'text' or 'title'.  (Default 'text')
         * @param $redirs Whether or not to list redirects.  (Default false)
         *
         * @return Associative array of search result metadata
         **/
        public function search($search, $limit = 10, $offset = 0, $namespace = 0, $what = 'text', $redirs = false)
        {
            $append = '';
            if ($limit != null) {
                $append .= '&srlimit='.urlencode($limit);
            }
            if ($offset != null) {
                $append .= '&sroffset='.urlencode($offset);
            }
            if ($namespace != null) {
                $append .= '&srnamespace='.urlencode($namespace);
            }
            if ($what != null) {
                $append .= '&srwhat='.urlencode($what);
            }
            if ($redirs == true) {
                $append .= '&srredirects=1';
            } else {
                $append .= '&srredirects=0';
            }
            $x = $this->http->get($this->apiurl.'?action=query&rawcontinue=1&list=search&format=php&srsearch='.urlencode($search).$append);
            $x = unserialize($x);

            return $x['query']['search'];
        }

        /**
         * Retrieve entries from the WikiLog.
         *
         * @param $user Username who caused the entry.  Null means anyone.  (Default null)
         * @param $title Object to which the entry refers.  Null means anything.  (Default null)
         * @param $limit Number of entries to return.  (Default 50)
         * @param $type Type of logs.  Null means any type.  (Default null)
         * @param $start Date to start enumerating logs.  Null means beginning/end depending on $dir.  (Default null)
         * @param $end Where to stop enumerating logs.  Null means whenever limit is satisfied or there are no more logs.  (Default null)
         * @param $dir Direction to enumerate logs.  "older" or "newer".  (Default 'older')
         *
         * @return Associative array of logs metadata
         **/
        public function logs($user = null, $title = null, $limit = 50, $type = null, $start = null, $end = null, $dir = 'older')
        {
            $append = '';
            if ($user != null) {
                $append .= '&leuser='.urlencode($user);
            }
            if ($title != null) {
                $append .= '&letitle='.urlencode($title);
            }
            if ($limit != null) {
                $append .= '&lelimit='.urlencode($limit);
            }
            if ($type != null) {
                $append .= '&letype='.urlencode($type);
            }
            if ($start != null) {
                $append .= '&lestart='.urlencode($start);
            }
            if ($end != null) {
                $append .= '&leend='.urlencode($end);
            }
            if ($dir != null) {
                $append .= '&ledir='.urlencode($dir);
            }
            $x = $this->http->get($this->apiurl.'?action=query&rawcontinue=1&format=php&list=logevents&leprop=ids|title|type|user|timestamp|comment|details'.$append);
            $x = unserialize($x);

            return $x['query']['logevents'];
        }

        /**
         * Retrieves metadata about a user's contributions.
         *
         * @param $user Username whose contributions we want to retrieve
         * @param $count Number of entries to return.  (Default 50)
         * @param[in,out] $continue Where to continue enumerating if part of a larger, split request.  This is filled with the next logical continuation value.  (Default null)
         *
         * @param $dir Which direction to enumerate from, "older" or "newer".  (Default 'older')
         *
         * @return Associative array of contributions metadata
         **/
        public function usercontribs($user, $count = 50, &$continue = null, $dir = 'older')
        {
            if ($continue != null) {
                $append = '&ucstart='.urlencode($continue);
            } else {
                $append = '';
            }
            $x = $this->http->get($this->apiurl.'?action=query&rawcontinue=1&format=php&list=usercontribs&ucuser='.urlencode($user).'&uclimit='.urlencode($count).'&ucdir='.urlencode($dir).$append);
            $x = unserialize($x);

            if (is_array($x) && array_key_exists('query-continue', $x) && array_key_exists('usercontribs', $x['query-continue']) && array_key_exists('ucstart', $x['query-continue']['usercontribs'])) {
                $continue = $x['query-continue']['usercontribs']['ucstart'];
            }

            return $x['query']['usercontribs'];
        }

        /**
         * Returns revision data (meta and/or actual).
         *
         * @param $page Page for which to return revision data for
         * @param $count Number of revisions to return. (Default 1)
         * @param $dir Direction to start enumerating multiple revisions from, "older" or "newer". (Default 'older')
         * @param $content Whether to return actual revision content, true or false.  (Default false)
         * @param $revid Revision ID to start at.  (Default null)
         * @param $wait Whether or not to wait a few seconds for the specific revision to become available.  (Default true)
         * @param $getrbtok Whether or not to retrieve a rollback token for the revision.  (Default false)
         * @param $dieonerror Whether or not to kill the process with an error if an error occurs.  (Default false)
         * @param $redirects Whether or not to follow redirects.  (Default false)
         *
         * @return Associative array of revision data
         **/
        public function revisions($page, $count = 1, $dir = 'older', $content = false, $revid = null, $wait = true, $getrbtok = false, $dieonerror = false, $redirects = false)
        {
            global $logger;
            $url = $this->apiurl.'?action=query&rawcontinue=1&prop=revisions&titles='.urlencode($page).'&rvlimit='.urlencode($count).'&rvprop=timestamp|ids|user|comment'.(($content) ? '|content' : '').'&format=php&meta=userinfo&rvdir='.urlencode($dir).(($revid !== null) ? '&rvstartid='.urlencode($revid) : '').(($getrbtok == true) ? '&rvtoken=rollback' : '').(($redirects == true) ? '&redirects' : '');
            $x = $this->http->get($url);
            $x = unserialize($x);
            if ($revid !== null) {
                $found = false;
                if (!isset($x['query']['pages']) or !is_array($x['query']['pages'])) {
                    if ($dieonerror == true) {
                        $logger->addError('No such page: ' . $url);
                        die();
                    } else {
                        return false;
                    }
                }
                foreach ($x['query']['pages'] as $data) {
                    if (!isset($data['revisions']) or !is_array($data['revisions'])) {
                        if ($dieonerror == true) {
                            $logger->addError('No such page: ' . $url);
                            die();
                        } else {
                            return false;
                        }
                    }
                    foreach ($data['revisions'] as $data2) {
                        if ($data2['revid'] == $revid) {
                            $found = true;
                        }
                    }
                    unset($data, $data2);
                    break;
                }

                if ($found == false) {
                    if ($wait == true) {
                        sleep(1);

                        return $this->revisions($page, $count, $dir, $content, $revid, false, $getrbtok, $dieonerror);
                    } else {
                        if ($dieonerror == true) {
                            $logger->addError('Revision error: ' . $url);
                            die();
                        }
                    }
                }
            }
            if ($x['query'] != null && array_key_exists('pages', $x['query'])) {
                foreach ($x['query']['pages'] as $key => $data) {
                    $continue = null;
                    if (is_array($x) && array_key_exists('query-continue', $x) && array_key_exists('revisions', $x['query-continue']) && array_key_exists('rvstartid', $x['query-continue']['revisions'])) {
                        $continue = $x['query-continue']['revisions']['rvstartid'];
                    }
                    if (array_key_exists('ns', $data)) {
                        $data['revisions']['ns'] = $data['ns'];
                    }
                    if (array_key_exists('title', $data)) {
                        $data['revisions']['title'] = $data['title'];
                    }
                    $data['revisions']['currentuser'] = $x['query']['userinfo']['name'];
                    $data['revisions']['continue'] = $continue;
                    $data['revisions']['pageid'] = $key;

                    return $data['revisions'];
                }
            }
        }

        /**
         * Enumerates user metadata.
         *
         * @param $start The username to start enumerating from.  Null means from the beginning.  (Default null)
         * @param $limit The number of users to enumerate.  (Default 1)
         * @param $group The usergroup to filter by.  Null means no filtering.  (Default null)
         * @param $requirestart Whether or not to require that $start be a valid username.  (Default false)
         * @param[out] $continue This is filled with the name to continue from next query.  (Default null)
         *
         * @return Associative array of user metadata
         **/
        public function users($start = null, $limit = 1, $group = null, $requirestart = false, &$continue = null)
        {
            $append = '';
            if ($start != null) {
                $append .= '&aufrom='.urlencode($start);
            }
            if ($group != null) {
                $append .= '&augroup='.urlencode($group);
            }
            $x = $this->http->get($this->apiurl.'?action=query&rawcontinue=1&list=allusers&format=php&auprop=blockinfo|editcount|registration|groups&aulimit='.urlencode($limit).$append);
            $x = unserialize($x);

            if (is_array($x) && array_key_exists('query-continue', $x) && array_key_exists('allusers', $x['query-continue']) && array_key_exists('aufrom', $x['query-continue']['allusers'])) {
                $continue = $x['query-continue']['allusers']['aufrom'];
            }

            if (($requirestart == true) and ($x['query']['allusers'][0]['name'] != $start)) {
                return false;
            }

            return $x['query']['allusers'];
        }

        /**
         * Get members of a category.
         *
         * @param $category Category to enumerate from
         * @param $count Number of members to enumerate.  (Default 500)
         * @param[in,out] $continue Where to continue enumerating from.  This is automatically filled in when run.  (Default null)
         *
         * @return Associative array of category member metadata
         **/
        public function categorymembers($category, $count = 500, &$continue = null)
        {
            if ($continue != null) {
                $append = '&cmcontinue='.urlencode($continue);
            } else {
                $append = '';
            }
            $category = 'Category:'.str_ireplace('category:', '', $category);
            $x = $this->http->get($this->apiurl.'?action=query&rawcontinue=1&list=categorymembers&cmtitle='.urlencode($category).'&format=php&cmlimit='.$count.$append);
            $x = unserialize($x);

            if (is_array($x) && array_key_exists('query-continue', $x) && array_key_exists('categorymembers', $x['query-continue']) && array_key_exists('cmcontinue', $x['query-continue']['categorymembers'])) {
                $continue = $x['query-continue']['categorymembers']['cmcontinue'];
            }

            return $x['query']['categorymembers'];
        }

        /**
         * Enumerate all categories.
         *
         * @param[in,out] $start Where to start enumerating.  This is updated automatically with the value to continue from.  (Default null)
         *
         * @param $limit Number of categories to enumerate.  (Default 50)
         * @param $dir Direction to enumerate in.  'ascending' or 'descending'.  (Default 'ascending')
         * @param $prefix Only enumerate categories with this prefix.  (Default null)
         *
         * @return Associative array of category list metadata
         **/
        public function listcategories(&$start = null, $limit = 50, $dir = 'ascending', $prefix = null)
        {
            $append = '';
            if ($start != null) {
                $append .= '&acfrom='.urlencode($start);
            }
            if ($limit != null) {
                $append .= '&aclimit='.urlencode($limit);
            }
            if ($dir != null) {
                $append .= '&acdir='.urlencode($dir);
            }
            if ($prefix != null) {
                $append .= '&acprefix='.urlencode($prefix);
            }

            $x = $this->http->get($this->apiurl.'?action=query&rawcontinue=1&list=allcategories&acprop=size&format=php'.$append);
            $x = unserialize($x);

            if (is_array($x) && array_key_exists('query-continue', $x) && array_key_exists('allcategories', $x['query-continue']) && array_key_exists('acfrom', $x['query-continue']['allcategories'])) {
                $start = $x['query-continue']['allcategories']['acfrom'];
            }

            return $x['query']['allcategories'];
        }

        /**
         * Enumerate all backlinks to a page.
         *
         * @param $page Page to search for backlinks to
         * @param $count Number of backlinks to list.  (Default 500)
         * @param[in,out] $continue Where to start enumerating from.  This is automatically filled in.  (Default null)
         *
         * @param $filter Whether or not to include redirects.  Acceptible values are 'all', 'redirects', and 'nonredirects'.  (Default null)
         *
         * @return Associative array of backlink metadata
         **/
        public function backlinks($page, $count = 500, &$continue = null, $filter = null)
        {
            if ($continue != null) {
                $append = '&blcontinue='.urlencode($continue);
            } else {
                $append = '';
            }
            if ($filter != null) {
                $append .= '&blfilterredir='.urlencode($filter);
            }

            $x = $this->http->get($this->apiurl.'?action=query&rawcontinue=1&list=backlinks&bltitle='.urlencode($page).'&format=php&bllimit='.$count.$append);
            $x = unserialize($x);

            if (is_array($x) && array_key_exists('query-continue', $x) && array_key_exists('backlinks', $x['query-continue']) && array_key_exists('blcontinue', $x['query-continue']['backlinks'])) {
                $continue = $x['query-continue']['backlinks']['blcontinue'];
            }

            return $x['query']['backlinks'];
        }

        /**
         * Gets a list of transcludes embedded in a page.
         *
         * @param $page Page to look for transcludes in
         * @param $count Number of transcludes to list.  (Default 500)
         * @param[in,out] $continue Where to start enumerating from.  This is automatically filled in.  (Default null)
         *
         * @return Associative array of transclude metadata
         **/
        public function embeddedin($page, $count = 500, &$continue = null)
        {
            if ($continue != null) {
                $append = '&eicontinue='.urlencode($continue);
            } else {
                $append = '';
            }
            $x = $this->http->get($this->apiurl.'?action=query&rawcontinue=1&list=embeddedin&eititle='.urlencode($page).'&format=php&eilimit='.$count.$append);
            $x = unserialize($x);

            if (is_array($x) && array_key_exists('query-continue', $x) && array_key_exists('embeddedin', $x['query-continue']) && array_key_exists('eicontinue', $x['query-continue']['embeddedin'])) {
                $continue = $x['query-continue']['embeddedin']['eicontinue'];
            }

            return $x['query']['embeddedin'];
        }

        /**
         * Gets a list of pages with a common prefix.
         *
         * @param $prefix Common prefix to search for
         * @param $namespace Numeric namespace to filter on.  (Default 0)
         * @param $count Number of pages to list.  (Default 500)
         * @param[in,out] $continue Where to start enumerating from.  This is automatically filled in.  (Default null)
         *
         * @return Associative array of page metadata
         **/
        public function listprefix($prefix, $namespace = 0, $count = 500, &$continue = null)
        {
            $append = '&apnamespace='.urlencode($namespace);
            if ($continue != null) {
                $append .= '&apfrom='.urlencode($continue);
            }
            $x = $this->http->get($this->apiurl.'?action=query&rawcontinue=1&list=allpages&apprefix='.urlencode($prefix).'&format=php&aplimit='.$count.$append);
            $x = unserialize($x);

            if (is_array($x) && array_key_exists('query-continue', $x) && array_key_exists('allpages', $x['query-continue']) && array_key_exists('apfrom', $x['query-continue']['allpages'])) {
                $continue = $x['query-continue']['allpages']['apfrom'];
            }
            if (!array_key_exists('query', $x)) {
                return;
            }

            return $x['query']['allpages'];
        }

        /**
         * Edits a page.
         *
         * @param $page Page name to edit
         * @param $data Data to post to page
         * @param $summary Edit summary to use
         * @param $minor Whether or not to mark edit as minor.  (Default false)
         * @param $bot Whether or not to mark edit as a bot edit.  (Default true)
         * @param $wpStarttime Time in MW TS format of beginning of edit.  (Default now)
         * @param $wpEdittime Time in MW TS format of last edit to that page.  (Default correct)
         *
         * @return bool True on success, false on failure
         **/
        public function edit($page, $data, $summary = '', $minor = false, $bot = true, $wpStarttime = null, $wpEdittime = null, $checkrun = true)
        {
            global $run, $user, $logger;

            $wpq = new wikipediaquery();
            $wpq->queryurl = str_replace('api.php', 'query.php', $this->apiurl);

            if ($checkrun == true) {
                if (!preg_match('/(yes|enable|true)/iS', ((isset($run)) ? $run : $wpq->getpage('User:'.$user.'/Run')))) {
                    return false;
                }
            } /* Check /Run page */

            $params = array(
                'action' => 'edit',
                'format' => 'php',
                'assert' => 'bot',
                'title' => $page,
                'text' => $data,
                'token' => $this->getedittoken(),
                'summary' => $summary,
                ($minor ? 'minor' : 'notminor') => '1',
                ($bot ? 'bot' : 'notbot') => '1',
            );

            if ($wpStarttime !== null) {
                $params['starttimestamp'] = $wpStarttime;
            }
            if ($wpEdittime !== null) {
                $params['basetimestamp'] = $wpEdittime;
            }

            $x = $this->http->post($this->apiurl, $params);
            $logger->addDebug($x);
            $x = unserialize($x);
            if ($x['edit']['result'] == 'Success') {
                return true;
            }
            if ($x['error']['code'] == 'badtoken') {
                $this->gettokens('Main Page', true);
                if ($this->getedittoken() == '') {
                    if ($this->login($this->user, $this->pass)) {
                        $this->gettokens('Main Page', true);
                    } else {
                        $logger->addError('Bah! Could not login!');

                        return false;
                    }
                }

                return $this->edit($page, $data, $summary, $minor, $bot, $wpStarttime, $wpEdittime, $checkrun);
            } else {
                return false;
            }
        }

        /**
         * Moves a page.
         *
         * @param $old Name of page to move
         * @param $new New page title
         * @param $reason Move summary to use
         **/
        public function move($old, $new, $reason)
        {
            global $logger;
            $tokens = $this->gettokens($old);
            $params = array(
                'action' => 'move',
                'format' => 'php',
                'from' => $old,
                'to' => $new,
                'token' => $tokens['movetoken'],
                'reason' => $reason,
            );

            $x = $this->http->post($this->apiurl, $params);
            $x = unserialize($x);
            $logger->addInfo($x);
        }

        /**
         * Rollback an edit.
         *
         * @param $title Title of page to rollback
         * @param $user Username of last edit to the page to rollback
         * @param $reason Edit summary to use for rollback
         * @param $token Rollback token.  If not given, it will be fetched.  (Default null)
         **/
        public function rollback($title, $user, $reason, $token = null)
        {
            global $logger;
            if (($token == null) or ($token == '')) {
                $token = $this->revisions($title, 1, 'older', false, null, true, true);
                if ($token[0]['user'] == $user) {
                    $token = $token[0]['rollbacktoken'];
                } else {
                    return false;
                }
            }
            $params = array(
                'action' => 'rollback',
                'format' => 'php',
                'title' => $title,
                'user' => $user,
                'summary' => $reason,
                'token' => $token,
                'markbot' => 0,
            );

            $logger->addDebug('Posting to API: '.$params);
            $x = $this->http->post($this->apiurl, $params);
            $x = unserialize($x);
            $logger->addInfo($x);

            return (isset($x['rollback']['summary']) and isset($x[ 'rollback' ][ 'revid' ]) and $x[ 'rollback' ][ 'revid' ])
                ? true
                : false;
        }
    }

    /**
     * This class is for interacting with Wikipedia's browser interface, index.php.
     * Many of these functions are deprecated.
     **/
    class wikipediaindex
    {
        private $http;
        private $postinterval = 0;
        private $lastpost;
        private $edittoken;

        /**
         * This is our constructor.
         **/
        public function __construct()
        {
            global $__wp__http;
            if (!isset($__wp__http)) {
                $__wp__http = new http();
            }
            $this->http = &$__wp__http;
        }

        /**
         * Post data to a page, nicely.
         *
         * @param $page Page title
         * @param $data Data to post to page
         * @param $summery Edit summary.  (Default '')
         * @param $minor Whether to mark edit as minor.  (Default false)
         * @param $rv Revision data.  If not given, it will be fetched.  (Default null)
         * @param $bot Whether to mark edit as bot.  (Default true)
         *
         * @return HTML data from the page
         *
         * @deprecated
         * @see wikipediaapi::edit
         **/
        public function post($page, $data, $summery = '', $minor = false, $rv = null, $bot = true)
        {
            global $user;
            global $maxlag;
            global $irc;
            global $irctechchannel;
            global $run;
            global $maxlagkeepgoing;

            $wpq = new wikipediaquery();
            $wpq->queryurl = str_replace('index.php', 'query.php', $this->indexurl);
            $wpapi = new wikipediaapi();
            $wpapi->apiurl = str_replace('index.php', 'api.php', $this->indexurl);

            if ((!$this->edittoken) or ($this->edittoken == '')) {
                $this->edittoken = $wpapi->getedittoken();
            }
            if ($rv == null) {
                $rv = $wpapi->revisions($page, 1, 'older', true);
            }
            if (!$rv[0]['*']) {
                $rv[0]['*'] = $wpq->getpage($page);
            }

            $now = gmdate('YmdHis', time());
            $token = htmlspecialchars($this->edittoken);
            $tmp = date_parse($rv[0]['timestamp']);
            $edittime = gmdate('YmdHis', gmmktime($tmp['hour'], $tmp['minute'], $tmp['second'], $tmp['month'], $tmp['day'], $tmp['year']));
            $html = "<input type='hidden' value=\"{$now}\" name=\"wpStarttime\" />\n";
            $html .= "<input type='hidden' value=\"{$edittime}\" name=\"wpEdittime\" />\n";
            $html .= "<input type='hidden' value=\"{$token}\" name=\"wpEditToken\" />\n";
            $html .= '<input name="wpAutoSummary" type="hidden" value="'.md5('').'" />'."\n";

            if (preg_match('/'.preg_quote('{{nobots}}', '/').'/iS', $rv[0]['*'])) {
                return false;
            }        /* Honor the bots flags */
            if (preg_match('/'.preg_quote('{{bots|allow=none}}', '/').'/iS', $rv[0]['*'])) {
                return false;
            }
            if (preg_match('/'.preg_quote('{{bots|deny=all}}', '/').'/iS', $rv[0]['*'])) {
                return false;
            }
            if (preg_match('/'.preg_quote('{{bots|deny=', '/').'(.*)'.preg_quote('}}', '/').'/iS', $rv[0]['*'], $m)) {
                if (in_array(explode(',', $m[1]), $user)) {
                    return false;
                }
            } /* /Honor the bots flags */
            if (!preg_match('/'.preg_quote($user, '/').'/iS', $rv['currentuser'])) {
                return false;
            } /* We need to be logged in */
            if (!preg_match('/(yes|enable|true)/iS', ((isset($run)) ? $run : $wpq->getpage('User:'.$user.'/Run')))) {
                return false;
            } /* Check /Run page */

            $x = $this->forcepost($page, $data, $summery, $minor, $html, $maxlag, $maxlagkeepgoing, $bot); /* Go ahead and post. */
            $this->lastpost = time();

            return $x;
        }

        /**
         * Post data to a page.
         *
         * @param $page Page title
         * @param $data Data to post to page
         * @param $summery Edit summary.  (Default '')
         * @param $minor Whether to mark edit as minor.  (Default false)
         * @param $edithtml HTML from the edit form.  If not given, it will be fetched.  (Default null)
         * @param $maxlag Maxlag for posting.  (Default null)
         * @param $mlkg Whether to keep going after encountering a maxlag error and sleeping or not.  (Default null)
         * @param $bot Whether to mark edit as bot.  (Default true)
         *
         * @return HTML data from the page
         *
         * @deprecated
         * @see wikipediaapi::edit
         **/
        public function forcepost($page, $data, $summery = '', $minor = false, $edithtml = null, $maxlag = null, $mlkg = null, $bot = true)
        {
            $post['wpSection'] = '';
            $post['wpScrolltop'] = '';
            if ($minor == true) {
                $post['wpMinoredit'] = 1;
            }
            $post['wpTextbox1'] = $data;
            $post['wpSummary'] = $summery;
            if ($edithtml == null) {
                $html = $this->http->get($this->indexurl.'?title='.urlencode($page).'&action=edit');
            } else {
                $html = $edithtml;
            }
            preg_match('|\<input type\=\\\'hidden\\\' value\=\"(.*)\" name\=\"wpStarttime\" /\>|U', $html, $m);
            $post['wpStarttime'] = $m[1];
            preg_match('|\<input type\=\\\'hidden\\\' value\=\"(.*)\" name\=\"wpEdittime\" /\>|U', $html, $m);
            $post['wpEdittime'] = $m[1];
            preg_match('|\<input type\=\\\'hidden\\\' value\=\"(.*)\" name\=\"wpEditToken\" /\>|U', $html, $m);
            $post['wpEditToken'] = $m[1];
            preg_match('|\<input name\=\"wpAutoSummary\" type\=\"hidden\" value\=\"(.*)\" /\>|U', $html, $m);
            $post['wpAutoSummary'] = $m[1];
            if ($maxlag != null) {
                $x = $this->http->post($this->indexurl.'?title='.urlencode($page).'&action=submit&maxlag='.urlencode($maxlag).'&bot='.(($bot == true) ? '1' : '0'), $post);
                if (preg_match('/Waiting for ([^ ]*): ([0-9.-]+) seconds lagged/S', $x, $lagged)) {
                    global $irc;
                    if (is_resource($irc)) {
                        global $irctechchannel;
                        foreach (explode(',', $irctechchannel) as $y) {
                            fwrite($irc, 'PRIVMSG '.$y.' :'.$lagged[1].' is lagged out by '.$lagged[2].' seconds. ('.$lagged[0].')'."\n");
                        }
                    }
                    sleep(10);
                    if ($mlkg != true) {
                        return false;
                    } else {
                        $x = $this->http->post($this->indexurl.'?title='.urlencode($page).'&action=submit&bot='.(($bot == true) ? '1' : '0'), $post);
                    }
                }

                return $x;
            } else {
                return $this->http->post($this->indexurl.'?title='.urlencode($page).'&action=submit&bot='.(($bot == true) ? '1' : '0'), $post);
            }
        }

        /**
         * Get a diff.
         *
         * @param $title Page title to get the diff of
         * @param $oldid Old revision ID
         * @param $id New revision ID
         * @param $wait Whether or not to wait for the diff to become available.  (Default true)
         *
         * @return array of added data, removed data, and a rollback token if one was fetchable
         **/
        public function diff($title, $oldid, $id, $wait = true)
        {
            global $logger;
            $deleted = '';
            $added = '';

            $url = $this->indexurl.'?title='.urlencode($title).'&action=render&diff='.urlencode($id).'&oldid='.urlencode($oldid).'&diffonly=1';
            $html = $this->http->get($url);

            if (preg_match_all('/\&amp\;(oldid\=)(\d*)\\\'\>(Revision as of|Current revision as of)/USs', $html, $m, PREG_SET_ORDER)) {
                if ((($oldid != $m[0][2]) and (is_numeric($oldid))) or (($id != $m[1][2]) and (is_numeric($id)))) {
                    if ($wait == true) {
                        sleep(1);

                        return $this->diff($title, $oldid, $id, false);
                    } else {
                        $logger->addError('OLDID as detected: '.$m[0][2].' Wanted: '.$oldid);
                        $logger->addError('NEWID as detected: '.$m[1][2].' Wanted: '.$id);
                        $logger->addDebug($html);
                        $logger->addError('Revision error: ' . $url);
                        die();
                    }
                }
            }

            if (preg_match_all('/\<td class\=(\"|\\\')diff-addedline\1\>\<div\>(.*)\<\/div\>\<\/td\>/USs', $html, $m, PREG_SET_ORDER)) {
                foreach ($m as $x) {
                    $added .= htmlspecialchars_decode(strip_tags($x[2]))."\n";
                }
            }

            if (preg_match_all('/\<td class\=(\"|\\\')diff-deletedline\1\>\<div\>(.*)\<\/div\>\<\/td\>/USs', $html, $m, PREG_SET_ORDER)) {
                foreach ($m as $x) {
                    $deleted .= htmlspecialchars_decode(strip_tags($x[2]))."\n";
                }
            }

            if (preg_match('/action\=rollback\&amp\;from\=.*\&amp\;token\=(.*)\"/US', $html, $m)) {
                $rbtoken = $m[1];
                $rbtoken = urldecode($rbtoken);

                return array($added, $deleted, $rbtoken);
            }

            return array($added, $deleted);
        }

        /**
         * Rollback an edit.
         *
         * @param $title Page title to rollback
         * @param $user Username of last edit to the page to rollback
         * @param $reason Reason to rollback.  If null, default is generated.  (Default null)
         * @param $token Rollback token to use.  If null, it is fetched.  (Default null)
         * @param $bot Whether or not to mark as bot.  (Default true)
         *
         * @return HTML or false if failure
         *
         * @deprecated
         * @see wikipediaapi::rollback
         **/
        public function rollback($title, $user, $reason = null, $token = null, $bot = true)
        {
            global $logger;
            if (($token == null) or (!$token)) {
                $wpapi = new wikipediaapi();
                $wpapi->apiurl = str_replace('index.php', 'api.php', $this->indexurl);
                $token = $wpapi->revisions($title, 1, 'older', false, null, true, true);
                if ($token[0]['user'] == $user) {
                    $token = $token[0]['rollbacktoken'];
                } else {
                    return false;
                }
            }
            $x = $this->http->get($this->indexurl.'?title='.urlencode($title).'&action=rollback&from='.urlencode($user).'&token='.urlencode($token).(($reason != null) ? '&summary='.urlencode($reason) : '').'&bot='.(($bot == true) ? '1' : '0'));

            $logger->addInfo('Rollback return: '.$x);
            if (!preg_match('/action complete/iS', $x)) {
                return false;
            }

            return $x;
        }

        /**
         * Move a page.
         *
         * @param $old Page title to move
         * @param $new New title to move to
         * @param $reason Move page summary
         *
         * @return HTML page
         *
         * @deprecated
         * @see wikipediaapi::move
         **/
        public function move($old, $new, $reason)
        {
            $wpapi = new wikipediaapi();
            $wpapi->apiurl = str_replace('index.php', 'api.php', $this->indexurl);
            if ((!$this->edittoken) or ($this->edittoken == '')) {
                $this->edittoken = $wpapi->getedittoken();
            }

            $token = htmlspecialchars($this->edittoken);

            $post = array(
                    'wpOldTitle' => $old,
                    'wpNewTitle' => $new,
                    'wpReason' => $reason,
                    'wpWatch' => '0',
                    'wpEditToken' => $token,
                    'wpMove' => 'Move page',
                );

            return $this->http->post($this->indexurl.'?title=Special:Movepage&action=submit', $post);
        }

        /**
         * Uploads a file.
         *
         * @param $page Name of page on the wiki to upload as
         * @param $file Name of local file to upload
         * @param $desc Content of the file description page
         *
         * @return HTML content
         **/
        public function upload($page, $file, $desc)
        {
            $post = array(
                    'wpUploadFile' => '@'.$file,
                    'wpSourceType' => 'file',
                    'wpDestFile' => $page,
                    'wpUploadDescription' => $desc,
                    'wpLicense' => '',
                    'wpWatchthis' => '0',
                    'wpIgnoreWarning' => '1',
                    'wpUpload' => 'Upload file',
                );

            return $this->http->post($this->indexurl.'?title=Special:Upload&action=submit', $post);
        }

        /**
         * Check if a user has email enabled.
         *
         * @param $user Username to check whether or not the user has email enabled
         *
         * @return true or false depending on whether or not the user has email enabled
         **/
        public function hasemail($user)
        {
            $tmp = $this->http->get($this->indexurl.'?title=Special:EmailUser&target='.urlencode($user));
            if (stripos($tmp, 'No e-mail address') !== false) {
                return false;
            }

            return true;
        }

        /**
         * Sends an email to a user.
         *
         * @param $user Username to send email to
         * @param $subject Subject of email to send
         * @param $body Body of email to send
         *
         * @return HTML content
         **/
        public function email($user, $subject, $body)
        {
            $wpapi = new wikipediaapi();
            $wpapi->apiurl = str_replace('index.php', 'api.php', $this->indexurl);
            if ((!$this->edittoken) or ($this->edittoken == '')) {
                $this->edittoken = $wpapi->getedittoken();
            }

            $post = array(
                    'wpSubject' => $subject,
                    'wpText' => $body,
                    'wpCCMe' => 0,
                    'wpSend' => 'Send',
                    'wpEditToken' => $this->edittoken,
                );

            return $this->http->post($this->indexurl.'?title=Special:EmailUser&target='.urlencode($user).'&action=submit', $post);
        }
    }
