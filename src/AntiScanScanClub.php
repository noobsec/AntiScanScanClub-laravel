<?php

namespace noobsec\AntiScanScanClub;

use Illuminate\Foundation;
use Illuminate\Http\Request;
use Storage;

class AntiScanScanClub
{
    /**
     * @var string $filterRules
    */
	private $filterRules = "filter_rules.json";

    /**
     * @var string $defaultBlacklists
    */
    private $defaultBlacklists = "blacklists.json";

    /**
     * AntiScanScanClub.
     *
     */
    public function __construct() {
    	$this->list = config('antiscanscanclub.list');
    	$this->options = config('antiscanscanclub.options');
    	$this->abort = ($this->options['return'] == NULL ? 403 : $this->options['return']);

    	$getBlacklists = $this->getBlacklists();
		$this->list_object = json_decode($getBlacklists, TRUE);
		if ($this->list_object === NULL) $this->purgeBlacklistsFile();
    }

    /**
	 * Get blacklists data
	 *
	 * @return string
	 *
	 * @throws \Exception
	*/
    private function getBlacklists() {
    	try {
            $get = Storage::get($this->list);
            return $get;
        } catch (\Illuminate\Contracts\Filesystem\FileNotFoundException $e) {
            $this->purgeBlacklistsFile();
        } catch (\Exception $e) {
            throw new \Exception("Error while get blacklists File: " . $e->getMessage(), 1);
        }
    }

    /**
     * Search client IP in blacklists file
     *
     * @param string $clientIp the visitor client IP
     * @return bool/integer
    */
    private function searchIp($clientIp) {
    	try {
    		if (($key = array_search($clientIp, array_column($this->list_object, "ip"), TRUE)) !== FALSE) {
		    	return $key;
		    } else {
		    	return FALSE;
		    }
    	} catch(\Exception $e) {
    		return FALSE;
    	}
    }

    /**
     * Check whether the client IP has been blocked or not
     *
     * @param string $clientIp the visitor client IP
     * @return void/bool
    */
    public function checkIp($clientIp) {
    	if ($this->searchIp($clientIp) !== FALSE) {
			return abort($this->abort);
    	} else {
			return FALSE;
    	}
    }

    /**
     * Prevention of illegal input based on filter rules file
     *
     * @param \Illuminate\Http\Request $request
     * @return void
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
    */
    public function filterInput(Request $request, $blocker = FALSE) {
    	$filterRules = __DIR__ . "/" . $this->filterRules;
    	$getRule = @file_get_contents($filterRules);

    	if ($getRule === FALSE) {
    		throw new \Exception("Error Processing filter rules File!", TRUE);	
    	}

    	$objectRules = json_decode($getRule, TRUE)['filters'];

    	foreach ($request->all() as $key => $value) {
	    	foreach ($objectRules as $key => $object) {
	    		if (preg_match("/" . $object['rule'] . "/", $value)) {
	    			if ($blocker === TRUE) $this->addToBlacklisted($request->ip(), $object['description'] . " (" . $value . ")");
	    			return abort($this->abort);
	    		}
	    	}
    	}

    	return FALSE;
    }

    /**
     * Add client IP to blacklists rule
     *
     * @param string $clientIp the visitor client IP
     * @param string $attack is attack type
     * @return bool
    */
    public function addToBlacklisted($clientIp, $attack = NULL) {
    	$add = $this->list_object;
    	$data = [
    		'ip' => $clientIp,
    		'attack' => ($attack = NULL ? "added manually" : $attack),
    		'time' => date('Y-m-d H:i:s')
    	];
    	array_push($add, $data);

    	return $this->writeToBlacklistsFile($add);
    }


	/**
     * Run rules to clean client IP from blacklists
     *
     * @return array
    */
	public function cronBlacklistedRules() {
		foreach ($this->list_object as $key => $object) {
			$getDiffInHours = (int) round(abs(strtotime('now') - strtotime($object['time'])) / 3600, 0);
			if ($getDiffInHours >= $this->options['expired']) {
				return $this->removeFromBlacklists($object['ip']);
			}
		}
	}


    /**
     * Remove client IP from blacklists rule
     *
     * @param string $clientIp the visitor client IP
     * @return array
    */
    public function removeFromBlacklists($clientIp) {
    	$searchIp = $this->searchIp($clientIp);
		if ($searchIp !== FALSE) {
	    	unset($this->list_object[$searchIp]);
	    }
	    return $this->writeToBlacklistsFile($this->list_object);
	}


	/**
     * Purge and/ clean all client IPs from blacklists
     *
     * @return callable
    */
    public function purgeBlacklistsFile() {
    	return $this->writeToBlacklistsFile([]);
    }


    /**
     * Write visitor data to blacklists file
     *
     * @param array $data the visitor data (such as client IP, attack type, etc)
     * @return bool
     *
     * @throws \Exception
    */
    private function writeToBlacklistsFile($data = []) {
    	$write = Storage::put(($this->list == NULL ? $this->defaultBlacklists : $this->list), json_encode($data, JSON_PRETTY_PRINT));

    	if ($write === FALSE) {
    		throw new \Exception("Error While writing to blacklists File!", TRUE);
    	} else {
    		return TRUE;
    	}
    }
}