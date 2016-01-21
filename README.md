HereAuth
===
> Your auth plugin is here, for you. The PocketMine auth plugin with the most customization ever.

Phar download:
* [Latest development build](compile/HereAuth_Dev.phar)
* [Latest beta build](compile/HereAuth_Beta.phar)
* [Latest release](compile/HereAuth_RC.phar)

```
Copyright (C) 2016 PEMapModder
This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Lesser General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.
```

Project created (first byte written): Jan 14 2016

Features
===
- [x] Authentication by typing password into chat
    - [x] sorry, no alternative, but there is an option to disallow passwords starting with slashes
- [x] Blocks player from talking password into chat directly
    - [x] disable this in config
- [x] Players can choose not to register (but using the /register command to start registering)
    - [x] enable this in config
- [x] Advanced session control system over PocketMine's default one
    - [x] PocketMine by default kicks the old player if a player joins with the same name as an online player.
    - [x] HereAuth checks if the players have the same client secret (and IP address too, optional in config). If they do, that means it is from the same genuine player, so kick the old player. If they aren't, this most likely means that the new player is trying to get the old player kicked.
- [ ] Multiple database types supported
    - [ ] MySQL
    - [x] filesystem (zlib-encoded JSON + SQLite3)
    - [x] External database through other plugins
- [x] Count-limit or rate-limit accounts per IP (account-throttle)
- [x] Time-based and attempts-based brute-force protection
- [ ] Customized automatic authentication methods
    - [ ] By "customized", I mean to customize _per player_! This basically refers to `/opt`
- [ ] Customized multi-factor authentication methods
- [ ] Customized data masking when player is not authenticated
    - [ ] Don't let impostors see what is in your inventory!
    - [ ] Don't let impostors see where you are!
    - [ ] Don't let impostors see what messages other plugins want to send to you!
- [x] Server-customized events to block when not authenticated
    - [x] Only blocks events that you want to block!
- [x] Enforced password control
    - [x] Maybe we are being nanny, but we should disallow players to have weak passwords!
- [x] Require custom extra information from players
- [x] Server-customized messages
- [x] Extensive audit logging
- [ ] An extensive API (W.I.P.)

Entry script
===
Open this phar directly with PHP binaries to automatically extract the config files.
