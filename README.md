# InvSee
[![](https://poggit.pmmp.io/shield.state/InvSee)](https://poggit.pmmp.io/p/InvSee)
[![](https://poggit.pmmp.io/shield.dl.total/InvSee)](https://poggit.pmmp.io/p/InvSee)

InvSee lets you view and modify player's inventory and ender chest inventory realtime.
You can view and modify offline players' inventories too!

Grab a pre-compiled InvSee `.phar` file from [Poggit CI](https://poggit.pmmp.io/p/invsee).

## Usage
### Accessing a player's inventory
Use `/invsee <player>` to access a player's inventory. This will open a double-chest inventory with the first 36 slots displaying the player's inventory contents in order.
The last row of slots displays the player's armor inventory contents.

![image](https://user-images.githubusercontent.com/15074389/163743159-aa7e7133-e3b7-4e67-8f18-8b219c6fd89b.png)

### Accessing a player's ender inventory
Similar to `/invsee <player>`, `/enderinvsee <player>` opens the player's ender inventory.

![image](https://user-images.githubusercontent.com/15074389/163743336-da683498-362f-4712-a8b6-3160b49833bd.png)

### More Features
InvSee features modules which can be configured through [`modules.yml`](https://github.com/BlockHorizons/InvSee/blob/master/resources/modules.yml). When a server starts, each module's state (enabled / disabled) is set to what is configured under `module-states` in `modules.yml`. However, modules can be enabled or disabled during runtime via the `/invseemod` command. These are all the InvSee modules at the moment:
| Name | Description |
| --- | --- |
| InvSee by Request | Allows players to request other players to view their inventory contents (in read-only mode). See https://github.com/BlockHorizons/InvSee/issues/10 for more information. |
| Portable Ender Chest | Allows players to access their own ender inventory via `/echest` command (the command name and its aliases are configurable). |
