<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class Search extends Controller
{
    private $search_users_url = "https://api.github.com/search/users";

    public function search(Request $request) {
        $error     = false;
        $error_msg = "";
        $results   = "";
        $repos     = array();

        if(!env("GITHUB_API_TOKEN")) {
            $error     = true;
            $error_msg = "Your GitHub API token is missing.";
        }
        else if($request->query('s') != "") {
            $search_url = $this->search_users_url . '?q=' . $request->query('s') . '&per_page=10';

            if(!$this->make_call($search_url, $results)) {
                $error     = true;
                $error_msg = "An error occurred with the API call.";
            }
            else {
                foreach($results->items as $item) {
                    $repos_results = "";

                    if(!$this->make_call($item->repos_url, $repos_results)) {
                        $error     = true;
                        $error_msg = "An error occurred with the API call.";
                    }
                    else {
                        $repos[$item->login] = $repos_results;
                    }
                }
            }
        }

        return view('search', ['error' => $error, 'error_msg' => $error_msg, 'results' => $results, 'repos' => $repos]);
    }

    public function make_call($url, &$results) {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Accept: application/vnd.github+json',
            'Authorization: Bearer ' . env("GITHUB_API_TOKEN")
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

        $results = curl_exec($ch);

        if(curl_getinfo($ch, CURLINFO_HTTP_CODE) != 200) {
            return(false);
        }

        $results = json_decode($results);

        curl_close($ch);

        return(true);
    }
}
