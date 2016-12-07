# Pi-hole-Block-Page
A user friendly "Website Blocked" page to add onto a https://pi-hole.net installation.

## What does this do?
The goal is to provide concise and relevant feedback to the end user, so they are better informed about the site they are trying to visit.

When a user browses to a blacklisted domain (For example, `doubleclick.net`), they will be presented with the block page (*as pictured below*). As an Internet connection is generally shared with a number of other people (and not just the person who set up Pi-hole on the network), this provides excellent visual feedback showing them what domain was blocked, what the most "notable" list it was featured in, and how they can go about resolving the issue (by emailing the Pi-hole admin).

In this case, `doubleclick.net` was found in `https://s3.amazonaws.com/lists.disconnect.me/simple_malvertising.txt` which I have classed as Malicious.

![Pi-hole Block Page](http://i.imgur.com/1sdGFt7.png)

When one attempts to access any non HTML resource (IE: not HTML, PHP, XML or RSS), the page will interpret this request as a "file" and will show a denied symbol, with the text "Blocked by Pi-hole" next to it.

## Install:
**DISCLAIMER:** This repo is a work in progress. For your sake, please consider all code to be *completely untested* until further notice. These instructions are provided as-is from the [original Pastebin link](http://pastebin.com/gtnM5ihU).


````
sudo wget -q https://raw.githubusercontent.com/WaLLy3K/Pi-hole-Block-Page/master/index.php -O /var/www/html/index.php
sudo chmod 755 /var/www/html/index.php
sudo sed -i 's:pihole/index.html:index.php:' /etc/lighttpd/lighttpd.conf
sudo service lighttpd force-reload
````

## Website Test Cases:

* http://192.168.1.x (Raspberry Pi IP) -- landing page
* http://pi.hole -- redirect to Pi-hole Admin Interface
* http://doubleclick.net/ -- site
* http://doubleclick.net?pihole=more -- site, more
* http://doubleclick.net/some/folder -- site
* http://doubleclick.net/some/content.php -- site
* http://doubleclick.net/some/content.php?query=true -- file
* http://doubleclick.net/some/content.php?query=true&pihole=more -- site, more
* http://doubleclick.net/file.exe -- file
* http://doubleclick.net/file.exe?query=true -- file
* http://doubleclick.net/some/image.gif -- file
* http://doubleclick.net/image.gif?query=true -- file
 
 
## Pre-Github changelog:

* 10SEP16: Removed $ from grep search
* 07DEC16: Cleaned up formatting, changed some variables & uploaded to GitHub
