<?php/* Wave FrameworkSitemap HandlerSitemap Handler is used to return sitemap.xml files, if a request is made to such a file. This handler either returns the existing /sitemap.xml file, or generates a new one based on sitemap files in /resources/ folder and the languages defined in configuration.Author and support: Kristo Vaher - kristo@waher.netLicense: GNU Lesser General Public License Version 3*/// INITIALIZATION	// Stopping all requests that did not come from Index Gateway	if(!isset($resourceAddress)){		header('HTTP/1.1 403 Forbidden');		die();	}	// Sitemap is always returned in XML format	header('Content-Type: text/xml;charset=utf-8;'); 		// This flag stores whether cache was used	$cacheUsed=false;// GENERATING SITEMAP	// Sitemap is generated only if it does not exist in root	if(!file_exists(__ROOT__.'sitemap.xml')){			// ASSIGNING PARAMETERS FROM REQUEST			// If filename includes & symbol, then system assumes it should be dynamically generated			$parameters=array_unique(explode('&',$resourceFile));			// Looking for cache			$cacheFilename=md5('sitemap.xml'.$resourceRequest).'.tmp';			$cacheDirectory=__ROOT__.'filesystem'.DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.substr($cacheFilename,0,2).DIRECTORY_SEPARATOR;			// If cache file exists then cache modified is considered that time			if(file_exists($cacheDirectory.$cacheFilename)){				$lastModified=filemtime($cacheDirectory.$cacheFilename);			} else {				// Otherwise it is server request time				$lastModified=$_SERVER['REQUEST_TIME'];			}			// Default cache timeout of one month, unless timeout is set			if(!isset($config['sitemap-cache-timeout'])){				$config['sitemap-cache-timeout']=14400; // Four hours			}					// GENERATING NEW SITEMAP OR LOADING FROM CACHE			// If sitemap cannot be found from cache, it is generated			if(in_array('nocache',$parameters) || ($lastModified==$_SERVER['REQUEST_TIME'] || $lastModified<($_SERVER['REQUEST_TIME']-$config['sitemap-cache-timeout']))){							// STATE AND DATABASE					// State stores a lot of settings that are taken into account during Sitemap generation					require(__ROOT__.'engine'.DIRECTORY_SEPARATOR.'class.www-state.php');					$state=new WWW_State($config);					// Connecting to database, if configuration is set					// Uncomment this if you actually need to use database connection for sitemap.txt file					// if(isset($config['database-name']) && $config['database-name']!='' && isset($config['database-type']) && isset($config['database-host']) && isset($config['database-username']) && isset($config['database-password'])){						// require(__ROOT__.'engine'.DIRECTORY_SEPARATOR.'class.www-database.php');						// $databaseConnection=new WWW_Database($config['database-type'],$config['database-host'],$config['database-name'],$config['database-username'],$config['database-password'],((isset($config['database-errors']))?$config['database-errors']:false),((isset($config['database-persistent']))?$config['database-persistent']:false));					// }									// GENERATING SITEMAP STRING 					// Sitemap XML string is stored here					$siteMapXML='';					// Sitemap can only be generated if system actually uses languages and sitemap files					if(!empty($state->data['languages'])){						// XML header						$siteMapXML.='<?xml version="1.0" encoding="utf-8"?>';												// Defining sitemap schema						$siteMapXML.='<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">';												// Root depends on whether website is forced to work on HTTPS or not						$root=((isset($config['https-limiter']) && $config['https-limiter']==true)?'https://':'http://').$_SERVER['HTTP_HOST'].$state->data['web-root'];												// Every language defined in state is generated for sitemap						foreach($state->data['languages'] as $language){													// Checking for existence of URL Map file							if(file_exists(__ROOT__.'overrides'.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.$language.'.sitemap.ini')){								// Overrides can be used if they are stored in /overrides/resources/ subfolder								$siteMap=parse_ini_file(__ROOT__.'overrides'.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.$language.'.sitemap.ini',true);							} elseif(file_exists(__ROOT__.'resources'.DIRECTORY_SEPARATOR.$language.'.sitemap.ini')){								// If there was no override, the URL Map is loaded from /resources/								$siteMap=parse_ini_file(__ROOT__.'resources'.DIRECTORY_SEPARATOR.$language.'.sitemap.ini',true);							}														// If first language does not require language node in URL's then it is ignored							if($language==$state->data['language'] && $state->data['enforce-first-language-url']==false){								$langRoot=$root;							} else {								$langRoot=$root.$language.'/';							}														// As long as sitemap file is not empty, the nodes are added to output							if(!empty($siteMap)){															// Read more about $node values from Sitemap files								foreach($siteMap as $url=>$node){																	// Hidden URL's and URL's with redirects are not placed in sitemap									if((!isset($node['permissions']) || $node['permissions']=='*' || $node['permissions']=='') && (!isset($node['hidden']) || $node['hidden']!=true) && !isset($node['temporary-redirect']) && !isset($node['permanent-redirect'])){																			// Building single URL node										$siteMapXML.='<url>';											// Location is the full URL of the page											if(strpos($url,':')!==false){												$url=explode(':',$url);												$siteMapXML.='<loc>'.$langRoot.$url[0].'</loc>';											} else {												$siteMapXML.='<loc>'.$langRoot.$url.'/</loc>';											}											// Priority is a value from 0.0 to 1.0 (default is 0.5). This tells how important this URL is in relation to other URL's											if(isset($node['priority'])){												$siteMapXML.='<priority>'.$node['priority'].'</priority>';											}											// This can be 'always','hourly','daily','weekly','monthly','yearly','never' and tell robots how often this URL changes											if(isset($node['change-frequency'])){												$siteMapXML.='<changefreq>'.$node['change-frequency'].'</changefreq>';											}											// It is possible to state in Sitemap when the URL was last modified											if(isset($node['last-modified'])){												// This should be in YYYY-MM-DD format												$siteMapXML.='<lastmod>'.$node['last-modified'].'</lastmod>';											}										$siteMapXML.='</url>';									}																	}															}						}												// Closing the sitemap tag						$siteMapXML.='</urlset>';											}									// WRITING TO CACHE									// Resource cache is cached in subdirectories, if directory does not exist then it is created					if(!is_dir($cacheDirectory)){						if(!mkdir($cacheDirectory,0777)){							trigger_error('Cannot create cache folder',E_USER_ERROR);						}					}										// Data is written to cache file					if(!file_put_contents($cacheDirectory.$cacheFilename,$siteMapXML)){						trigger_error('Cannot create resource cache',E_USER_ERROR);					}							} else {				// Notifying logger that cache was used				$cacheUsed=true;			}					// HEADERS			// If cache is used, then proper headers will be sent			if(in_array('nocache',$parameters)){				// user agent is told to cache these results for set duration				header('Cache-Control: public,max-age=0');				header('Expires: '.gmdate('D, d M Y H:i:s',$_SERVER['REQUEST_TIME']).' GMT');				header('Last-Modified: '.gmdate('D, d M Y H:i:s',$lastModified).' GMT');			} else {				// user agent is told to cache these results for set duration				header('Cache-Control: public,max-age='.$config['sitemap-cache-timeout']);				header('Expires: '.gmdate('D, d M Y H:i:s',($_SERVER['REQUEST_TIME']+$config['sitemap-cache-timeout'])).' GMT');				header('Last-Modified: '.gmdate('D, d M Y H:i:s',$lastModified).' GMT');			}			// Pragma header removed should the server happen to set it automatically			header_remove('Pragma');						// Content length of the file			$contentLength=filesize($cacheDirectory.$cacheFilename);			// Content length is defined that can speed up website requests, letting user agent to determine file size			header('Content-Length: '.$contentLength);					// OUTPUT			// Returning the file to user agent			readfile($cacheDirectory.$cacheFilename);			// File is deleted if cache was requested to be off			if(in_array('nocache',$parameters)){				unlink($cacheDirectory.$cacheFilename);			}	} else {			// RETURNING EXISTING SITEMAP					// This is technically considered as using cache			$cacheUsed=true;					// Last modified header			header('Last-Modified: '.gmdate('D, d M Y H:i:s',filemtime(__ROOT__.'sitemap.xml')).' GMT');			// Content length of the file			$contentLength=filesize(__ROOT__.'sitemap.xml');			// Content length is defined that can speed up website requests, letting user agent to determine file size			header('Content-Length: '.$contentLength);			// Since sitemap.xml did exist in root, it is simply returned			readfile(__ROOT__.'sitemap.xml');			}	// WRITING TO LOG	// If Logger is defined then request is logged and can be used for performance review later	if(isset($logger)){		// Assigning custom log data to logger		$logger->setCustomLogData(array('category'=>'sitemap','cache-used'=>$cacheUsed,'content-length-used'=>$contentLength,'database-query-count'=>((isset($databaseConnection))?$databaseConnection->queryCounter:0)));		// Writing log entry		$logger->writeLog('sitemap');	}?>