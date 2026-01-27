# QNAP-DownloadStation-Addons

Updated add-ons for old DownloadStation v5.0. (which can't be updated on older NAS-devices with QTS 4.3.3)

# Installation:

1. Copy the addon folders to your QNAP (only the 3 files each, not the .addon file):

limetorrents.fun/  
 ├── addon.build  
 ├── addon.json  
 └── limetorrents.fun.php

2. Run the following command to find the addons directory in your QNAP (usually /share/MD0_DATA/.qpkg/DSv3/usr/sbin/addons):

`find /share -name "ds-addon" 2>/dev/null`

3. Place them on the QNAP in the addons directory. SSH into your QNAP and run:

`cp -r [addon foldername] /share/MD0_DATA/.qpkg/DSv3/usr/sbin/addons/`

4. Open your Download Station app on your NAS, go to Settings, and click the refresh button in the "Add-ons" tab. It will load the newly uploaded add-ons.

5. Make sure you enable them if you plan to use them.
