<?php

namespace NetherGen;

use NetherGen\generator\NetherGen;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\block\Block;
use pocketmine\level\generator\Generator;
use pocketmine\utils\Config;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\Player;
use pocketmine\block\BlockFactory;
use pocketmine\block\Solid;
use pocketmine\math\Vector3;
use pocketmine\item\Item;
use NetherGen\generator\block\Portal;
use pocketmine\command\Command;       //用于测试
use pocketmine\command\CommandSender;   //用于测试
use pocketmine\command\ConsoleCommandSender;  //用于测试

class Main extends PluginBase implements Listener {
	
	private $temporalVector = null;

	public static function registerBiome(int $id, Biome $biome) {
		NetherGen::registerBiome($biome);
	}

	public function onEnable() {
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		Generator::addGenerator(NetherGen::class, "地狱生成器");           //添加生成器
		$this->getLogger()->info('§2【地狱产生器】启动中...');
		@mkdir($this->getDataFolder());
		$this->Config = new Config($this->getDataFolder() . 'Config.yml',Config::YAML,['是否启用地狱门' => '是']);
		$netherEnabled = $this->Config->get('是否启用地狱门');
	}
    /*指令用于测试*/
	public function onCommand(CommandSender $sender, Command $cmd, $label, array $args): bool {
		if($cmd->getName() == 'NetherGen'){
			$name = 'nether';
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

	public function onFlintSteel(PlayerInteractEvent $event){             //玩家打火石触摸事件
		$item = $event->getItem();
		$itemID = $event->getItem()->getID();			//获取玩家手持物品ID
		$player = $event->getPlayer();
		$block = $event->getBlock();
		$face = $event->getFace();
		$clickPos = $event->getTouchVector();
		if($itemID == 259){
			$this->onActivate($item,$block,$block,$face,$clickPos,$player);		//调用地狱门算法
		}
	}
	
	public function onActivate(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickPos, Player $player) : bool{         //地狱门算法
		$level = $player->getLevel();
		$netherEnabled = $this->Config->get('是否启用地狱门');
		$this->temporalVector = new Vector3(0, 0, 0);
	    if($blockClicked->getId() === Block::OBSIDIAN and $netherEnabled == '是'){//黑曜石 4*5最小 23*23最大
			//$level->setBlock($block, new Fire(), true);
			$tx = $blockClicked->getX();
			$ty = $blockClicked->getY();
			$tz = $blockClicked->getZ();
			//x方向
			$x_max = $tx;//x最大值
			$x_min = $tx;//x最小值
			for($x = $tx + 1; $level->getBlock($this->temporalVector->setComponents($x, $ty, $tz))->getId() == Block::OBSIDIAN; $x++){
				$x_max++;
			}
			for($x = $tx - 1; $level->getBlock($this->temporalVector->setComponents($x, $ty, $tz))->getId() == Block::OBSIDIAN; $x--){
				$x_min--;
			}
			$count_x = $x_max - $x_min + 1;//x方向方块
			if($count_x >= 4 and $count_x <= 23){//4 23
				$x_max_y = $ty;//x最大值时的y最大值
				$x_min_y = $ty;//x最小值时的y最大值
				for($y = $ty; $level->getBlock($this->temporalVector->setComponents($x_max, $y, $tz))->getId() == Block::OBSIDIAN; $y++){
					$x_max_y++;
				}
				for($y = $ty; $level->getBlock($this->temporalVector->setComponents($x_min, $y, $tz))->getId() == Block::OBSIDIAN; $y++){
					$x_min_y++;
				}
				$y_max = min($x_max_y, $x_min_y) - 1;//y最大值
				$count_y = $y_max - $ty + 2;//方向方块
				//Server::getInstance()->broadcastMessage("$y_max $x_max_y $x_min_y $x_max $x_min");
				if($count_y >= 5 and $count_y <= 23){//5 23
					$count_up = 0;//上面
					for($ux = $x_min; ($level->getBlock($this->temporalVector->setComponents($ux, $y_max, $tz))->getId() == Block::OBSIDIAN and $ux <= $x_max); $ux++){
						$count_up++;
					}
					//Server::getInstance()->broadcastMessage("$count_up $count_x");
					if($count_up == $count_x){
						for($px = $x_min + 1; $px < $x_max; $px++){
							for($py = $ty + 1; $py < $y_max; $py++){
								$level->setBlock($this->temporalVector->setComponents($px, $py, $tz), new Portal());
							}
						}
						return true;
					}
				}
			}
			//z方向
			$z_max = $tz;//z最大值
			$z_min = $tz;//z最小值
			for($z = $tz + 1; $level->getBlock($this->temporalVector->setComponents($tx, $ty, $z))->getId() == Block::OBSIDIAN; $z++){
				$z_max++;
			}
			for($z = $tz - 1; $level->getBlock($this->temporalVector->setComponents($tx, $ty, $z))->getId() == Block::OBSIDIAN; $z--){
				$z_min--;
			}
			$count_z = $z_max - $z_min + 1;
			if($count_z >= 4 and $count_z <= 23){//4 23
				$z_max_y = $ty;//z最大值时的y最大值
				$z_min_y = $ty;//z最小值时的y最大值
				for($y = $ty; $level->getBlock($this->temporalVector->setComponents($tx, $y, $z_max))->getId() == Block::OBSIDIAN; $y++){
					$z_max_y++;
				}
				for($y = $ty; $level->getBlock($this->temporalVector->setComponents($tx, $y, $z_min))->getId() == Block::OBSIDIAN; $y++){
					$z_min_y++;
				}
				$y_max = min($z_max_y, $z_min_y) - 1;//y最大值
				$count_y = $y_max - $ty + 2;//方向方块
				if($count_y >= 5 and $count_y <= 23){//5 23
					$count_up = 0;//上面
					for($uz = $z_min; ($level->getBlock($this->temporalVector->setComponents($tx, $y_max, $uz))->getId() == Block::OBSIDIAN and $uz <= $z_max); $uz++){
						$count_up++;
					}
					//Server::getInstance()->broadcastMessage("$count_up $count_z");
					if($count_up == $count_z){
						for($pz = $z_min + 1; $pz < $z_max; $pz++){
							for($py = $ty + 1; $py < $y_max; $py++){
								$level->setBlock($this->temporalVector->setComponents($tx, $py, $pz), new Portal());
							}
						}
						return true;
					}
				}
			}
			//return true;
		}
		return false;
	}
	
	
}
