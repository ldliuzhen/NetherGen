<?php

namespace NetherGen;

use NetherGen\generator\NetherGen;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\block\Block;
use pocketmine\level\generator\Generator;
use pocketmine\command\Command;       //用于测试
use pocketmine\command\CommandSender;   //用于测试
use pocketmine\command\ConsoleCommandSender;  //用于测试

class Main extends PluginBase implements Listener {

	public static function registerBiome(int $id, Biome $biome) {
		NetherGen::registerBiome($biome);
	}

	public function onEnable() {
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		Generator::addGenerator(NetherGen::class, "地狱生成器");           //添加生成器
		$this->getLogger()->info('§2【地狱产生器】启动中...');
	}
    /*指令用于测试*/
	public function onCommand(CommandSender $sender, Command $cmd, $label, array $args): bool {
		if(!$cmd->getName()){
			$name = 'Nether';
			$generator = Generator::getGenerator("地狱生成器");
			$seed = $this->generateRandomSeed();   //调用自定义function—随机SEED
			$options = [];
			$options["preset"] = json_encode($options);      //作用未知，来自BetterGen(未改动)
			if ((int)$seed == 0/*String*/) {
				$seed = $this->generateRandomSeed();
			}
			$this->getServer()->generateLevel($name, $seed, $generator, $options);
			$this->getServer()->loadLevel($name);
			return true;
		}
		return true;
	}

	public function generateRandomSeed(): int {           //产生随机seed,来自BetterGen(未改动)
		return (int)round(rand(0, round(time()) / memory_get_usage(true)) * (int)str_shuffle("127469453645108") / (int)str_shuffle("12746945364"));
	}

}
