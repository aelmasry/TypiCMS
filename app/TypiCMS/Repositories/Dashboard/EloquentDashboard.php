<?php namespace TypiCMS\Repositories\Dashboard;

use DB;
use Str;
use Sentry;
use Config;
use Request;

use TypiCMS\Repositories\RepositoriesAbstract;
use TypiCMS\Services\Cache\CacheInterface;

class EloquentDashboard extends RepositoriesAbstract implements DashboardInterface {

	// Class expects an Eloquent model and a cache interface
	public function __construct(CacheInterface $cache)
	{
		$this->cache = $cache;
	}

	public function getWelcomeMessage()
	{
		$ch = curl_init('http://www.typi.be/welcomeMessage_fr.html');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		$welcomeMessage = curl_exec($ch);
		if (curl_getinfo($ch, CURLINFO_HTTP_CODE) >= 400) {
			return '';
		}
		curl_close($ch);
		return $welcomeMessage;
	}


	public function getDashboardModules()
	{
		// Build the cache key, unique per model slug
		$key = md5('dashboardmodules');

		if ( Request::segment(1) != 'admin' and $this->cache->active('public') and $this->cache->has($key) ) {
			return $this->cache->get($key);
		}

		// Item not cached, retrieve it
		$modulesArray = Config::get('app.modules');
		$modulesForDashboard = array();
		foreach ($modulesArray as $module => $property) {
			$lowerName = strtolower($module);
			if ($property['dashboard'] and Sentry::getUser()->hasAccess('admin.' . $lowerName . '.index')) {
				$modulesForDashboard[$module]['name'] = $module;
				$modulesForDashboard[$module]['route'] = $lowerName;
				$modulesForDashboard[$module]['title'] = Str::title(trans_choice('modules.'.$lowerName.'.'.$lowerName), 2));
				$modulesForDashboard[$module]['count'] = DB::table($lowerName)->count();
			}
		}

		// Store in cache for next request
		$this->cache->put($key, $modulesForDashboard);

		return $modulesForDashboard;
	}

}