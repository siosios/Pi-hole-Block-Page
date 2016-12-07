<?php
# Pi-hole Block Page: Show "Website Blocked" on blacklisted domains
# by WaLLy3K 06SEP16 (Updated 07DEC16) for Pi-hole

# If user browses to Raspberry Pi's IP manually, where should they be directed?
# Assumes default folder of /var/www/html/, leave blank for none
$landing = "landing.php";

# Who should whitelist emails go to?
$email = "admin@domain.com";

# What is the name of your Pi-hole domain, if any?
$domain = "";

# Define "flagType" of indivudual adlists.list URLs
# Please add any domains here that has been manually placed in adlists.list
# TODO: This could be done better
$suspicious = array(
  "https://raw.githubusercontent.com/StevenBlack/hosts/master/hosts",
  "http://adblock.gjtech.net/?format=unix-hosts",
  "http://sysctl.org/cameleon/hosts",
  "https://hosts-file.net/ad_servers.txt",
  "http://adblock.mahakala.is",
  "https://raw.githubusercontent.com/crazy-max/WindowsSpyBlocker/master/data/hosts/win10/spy.txt",
  "http://securemecca.com/Downloads/hosts.txt",
  "https://raw.githubusercontent.com/BreakingTheNews/BreakingTheNews.github.io/master/hosts",
  "https://raw.githubusercontent.com/Dawsey21/Lists/master/main-blacklist.txt",
  "https://raw.github.com/notracking/hosts-blocklists/master/hostnames.txt",
  "https://raw.github.com/notracking/hosts-blocklists/master/domains.txt",
  "https://raw.githubusercontent.com/mat1th/Dns-add-block/master/hosts",
);

$advertising = array(
  "https://s3.amazonaws.com/lists.disconnect.me/simple_tracking.txt",
  "https://s3.amazonaws.com/lists.disconnect.me/simple_ad.txt",
  "http://optimate.dl.sourceforge.net/project/adzhosts/HOSTS.txt",
  "https://raw.githubusercontent.com/quidsup/notrack/master/trackers.txt",
);

$tracking = array(
  "https://s3.amazonaws.com/lists.disconnect.me/simple_tracking.txt",
  "https://raw.githubusercontent.com/crazy-max/WindowsSpyBlocker/master/data/hosts/win10/spy.txt",
  "https://raw.githubusercontent.com/quidsup/notrack/master/trackers.txt",
);

$malicious = array(
  "http://mirror1.malwaredomains.com/files/justdomains",
  "https://zeustracker.abuse.ch/blocklist.php?download=domainblocklist",
  "https://ransomwaretracker.abuse.ch/downloads/RW_DOMBL.txt",
  "http://malwaredomains.lehigh.edu/files/domains.txt",
);

# Define which URL extensions get rendered as "Website Blocked"
# Index files should always be rendered as "Website Blocked" anyway
$webRender = array('asp', 'htm', 'html', 'php', 'rss', 'xml');

# "Should" prevent arbitrary commands from being run as www-data when using grep
$serverName = escapeshellcmd($_SERVER['SERVER_NAME']);

# Retrieve server URI extension (EG: jpg, exe, php)
$uriExt = pathinfo($_SERVER['REQUEST_URI'], PATHINFO_EXTENSION);

# Define URI types
if ($serverName == "pi.hole") {
  header('Location: admin');
}elseif (!empty($landing) && $serverName == $_SERVER['SERVER_ADDR'] || $serverName == $domain) {
  # When browsing to RPi, redirect to custom landing page
  include $landing;
  exit();
}elseif (substr_count($_SERVER['REQUEST_URI'], "pihole=more")) {
  # "pihole=more" is set
  $uriType = "more";
}elseif (in_array($uriExt, $webRender)) {
  # "Safe" file extension
  $uriType = "site";
}elseif (!empty($uriExt) || substr_count($uri, "?")) {
  # Get file extension, or check for query string
  $uriType = "file";
}else{
  $uriType = "site";
}

# Handle incoming URI types
if ($uriType == "file"){
  # Serve this SVG to URI's defined as file
  die('<svg width="110" height="18"><defs><style>.c1 {fill: #942525; fill-rule: evenodd;} .c2 {fill: rgba(0,0,0,0.3); font-size: 11px; font-family: Arial;}</style></defs>
    <path class="c1" d="M8,0A8,8,0,1,1,0,8,8,8,0,0,1,8,0ZM8,2A6,6,0,1,1,2,8,6,6,0,0,1,8,2Z"/><path class="c1" d="M2,12.625L11.625,3,13,4.375,3.375,14Z"/>
    <text x="18" y="12" class="c2">Blocked by Pi-hole</text></svg>');
}else{
  # Some error handling
  $domainList = glob('/etc/pihole/*domains');
  if (empty($domainList)) die("[ERROR]: There are no blacklists in the Pi-Hole folder! Please update the list of ad-serving domains.");
  if (!file_exists("/etc/pihole/adlists.list")) die("[ERROR]: There is no 'adlists.list' in the Pi-Hole folder!");

  # Grep exact search $serverName within individual blocked .domains lists
  # Returning a numerically sorted array of the "list #" of matching .domains
  exec('grep "^0 '.$serverName.'" /etc/pihole/*domains | cut -d. -f2 | sort -un', $listMatches);

  # Get all URLs starting with "http" from adlists.list
  # $urlList array key expected to match .domains list # in $listMatches!!
  # This may not work if admin updates gravity, and later inserts a new hosts URL at anywhere but the end
  # Pi-hole seemingly will not update .domains correctly if this occurs, as of 10SEP16
  $urlList = array_values(preg_grep("/(^http)|(^www)/i", file('/etc/pihole/adlists.list', FILE_IGNORE_NEW_LINES)));

  # Return how many lists URL is featured in, and total lists count
  $featuredTotal = count(array_values(array_unique($listMatches)));
  $totalLists = count($urlList);

  # Featured total will be 0 for a manually blacklisted site
  # Or for a domain not found within "flagType" array
  if ($featuredTotal == "0") {
    $notableFlag = "Blacklisted manually";
  }else{
    # Define "Featured Flag"
    foreach ($listMatches as $num) {
      # Create a string of flags for URL
      if(in_array($urlList[$num], $suspicious)) $in .= "sus ";
      if(in_array($urlList[$num], $advertising)) $in .= "ads ";
      if(in_array($urlList[$num], $tracking)) $in .= "trc ";
      if(in_array($urlList[$num], $malicious)) $in .= "mal ";
      
      # Return value of worst flag to user (EG: Malicious more notable than Suspicious)
      if (substr_count($in, "sus")) $notableFlag = "Suspicious";
      if (substr_count($in, "ads")) $notableFlag = "Advertising";
      if (substr_count($in, "trc")) $notableFlag = "Tracking & Telemetry";
      if (substr_count($in, "mal")) $notableFlag = "Malicious";
      if (empty($in)) $notableFlag = "Unspecified Flag";
    }
  }

  # Probably redundant since this page should only display if dnsmasq working
  $piStatus = exec('pgrep dnsmasq | wc -l');
  if ($piStatus > "0") {
    $piInfo = "class='active'>Active &#10003;";
  }else{
    $piInfo = "class='inactive'>Offline &#10007;";
  }

  echo "<!DOCTYPE html><head>
      <meta charset='UTF-8'/>
      <title>Website Blocked</title>
      <link rel='stylesheet' href='style.css'/>
      <link rel='shortcut icon' href='/admin/img/favicon.png' type='image/png'/>
      <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'/>
      <meta name='robots' content='noindex,nofollow'/>
    </head><body><header id='block'>
      <h1><a href='/'>Website Blocked</a></h1>
      <div class='alt'>Pi-hole Status:<br/><span $piInfo</span></div>
    </header><main>
    <div class='blocked'>
      Access to the following site has been blocked:<br/>
      <span class='phmsg'>$serverName$foundText</span>
      This is primarily due to being flagged as:<br/>
      <span class='phmsg'>$notableFlag</span>
      If you have an ongoing use for this website, please <a href='mailto:$email?subject=Site Blocked: $serverName'>ask to have it whitelisted</a>.
    </div>
    <div class='buttons'><a class='safe' href='javascript:history.back()'>Back to safety</a>
  ";

  # More Information, for the technically inclined
  if ($uriType == "more" && $featuredTotal != "0") {
    # Remove pihole=more string for hyperlink
    $uriStrip = preg_replace("/.pihole=more/", "", $_SERVER['REQUEST_URI']);
    echo "&nbsp;<a class='warn' href='http://$serverName$uriStrip'>Less Info</a></div>";
    echo "<br/><div>This site is found in $featuredTotal of $totalLists .domains ".(count($listMatches) == 1 ? 'list' : 'lists').": ".implode(', ', $listMatches)."</div>";
    # Native scrolling on iOS is a nice touch
    echo "<div style='font-family: monospace; font-size: 0.8em;margin: 2px 0 0 8px; overflow: auto; white-space: nowrap; -webkit-overflow-scrolling: touch; width: 100%;'>";
    foreach ($listMatches as $num) {
      echo "  [$num]: <a href='$urlList[$num]'>$urlList[$num]</a><br/>";
    }
    echo "</div>";
  }elseif ($featuredTotal != "0") {
    # Strip query string for hyperlink
    $uriStrip = preg_replace("/\?.*/", "", $_SERVER['REQUEST_URI']);
    echo "&nbsp;<a class='warn' href='http://$serverName$uriStrip?pihole=more'>More Info</a></div>";
  }

  echo "  
    </main>
    <footer>Generated ".date('D g:i A, M d')." by Pi-hole ".exec('cd /etc/.pihole/ && git describe --tags --abbrev=0')."</footer>
    </body></html>
  ";
}
?>
