<?php

namespace PiggyAntiCheats;

use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\Listener;
use pocketmine\network\protocol\AdventureSettingsPacket;

class EventListener implements Listener {
    public function __construct($plugin) {
        $this->plugin = $plugin;
    }

    public function onJoin(PlayerJoinEvent $event) {
        $player = $event->getPlayer();
            $this->plugin->notified[$player->getName()] = true;
        }
    }

    public function onMove(PlayerMoveEvent $event) {
        $player = $event->getPlayer();
        $from = $event->getFrom();
        $to = $event->getTo();
        $this->plugin->blocks[$player->getName()] = + pow($to->x - $from->x, 2) + pow($to->z - $from->z, 2); //Don't get distance for y
        $this->plugin->blocksup[$player->getName()] = + ($to->y - $from->y); //Returns negative if going down :)
        if (isset($this->lasty[$player->getName()])) {
            if (floor($this->lasty[$player->getName()]) < floor($player->y)) {
                $player->sendMessage(str_replace("{player}", $player->getName(), $this->plugin->getMessage("fly")));
                $this->plugin->points[$player->getName()]++;
                $event->setCancelled();
            }
            $this->lasty[$player->getName()] = $player->y;
        }
    }

    public function onQuit(PlayerQuitEvent $event) {
        $player = $event->getPlayer();
        unset($this->plugin->blocks[$player->getName()]);
        unset($this->plugin->points[$player->getName()]);
    }

    public function onRecieve(DataPacketReceiveEvent $event) {
        $player = $event->getPlayer();
        $packet = $event->getPacket();
        if ($packet instanceof AdventureSettingsPacket) {
            if (($packet->allowFlight || $packet->isFlying) && $player->getAllowFlight() !== true) {
                foreach ($this->plugin->getServer()->getOnlinePlayers() as $p) {
                    if ($p->hasPermission("piggyanticheat.notify") && isset($this->plugin->notified[$player->getName()])) {
                        $player->sendMessage(str_replace("{player}", $player->getName(), $this->plugin->getMessage("fly")));
                    }
                }
                $this->plugin->points[$player->getName()]++;
            }
            if ($packet->noClip && $player->isSpectator() !== true) {
                foreach ($this->plugin->getServer()->getOnlinePlayers() as $p) {
                    if ($p->hasPermission("piggyanticheat.notify") && isset($this->plugin->notified[$player->getName()])) {
                        $player->sendMessage(str_replace("{player}", $player->getName(), $this->plugin->getMessage("no-clip")));
                    }
                }
                $this->plugin->points[$player->getName()]++;
            }
            $player->sendSettings();
            $event->setCancelled();
        }
    }

}
