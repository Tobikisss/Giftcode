<?php
namespace GiftCode;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\Player;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\ModalFormResponsePacket;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\item\Item;
use pocketmine\event\player\PlayerQuitEvent;
use GiftCode\FormEvent\Form;
use GiftCode\FormEvent\CustomForm;
use onebone\economyapi\EconomyAPI;

class Main extends PluginBase implements Listener {

    public $used;
    public $eco;
    public $giftcode;
    public $instance;
    public $formCount = 0;
    public $forms = [];
    public $file = new Config($this->plugin->getDataFolder() . "settings.yml", Config::YAML);

    public function onEnable() {
     $plugin = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI");
     if(is_null($plugin)) {
     $this->getServer()->shutdown();
     }else{
      $this->eco = EconomyAPI::getInstance();
     }
     $this->formCount = rand(0, 0xFFFFFFFF);
     $this->getServer()->getPluginManager()->registerEvents($this, $this);
     if(!is_dir($this->getDataFolder())) {
      mkdir($this->getDataFolder());
     }
     $this->used = new \SQLite3($this->getDataFolder() ."used-code.db");
     $this->used->exec("CREATE TABLE IF NOT EXISTS code (code);");
     $this->giftcode = new \SQLite3($this->getDataFolder() ."code.dn");
     $this->giftcode->exec("CREATE TABLE IF NOT EXISTS code (code);");
     $this->saveResource("settings.yml");
     $settings = new Config($this->getDataFolder() . "settings.yml", Config::YAML);
     }

    public function createCustomForm(callable $function = null) : CustomForm {
     $this->formCountBump();
     $form = new CustomForm($this->formCount, $function);
     $this->forms[$this->formCount] = $form;
     return $form;
    }

    public function formCountBump() : void {
     ++$this->formCount;
     if($this->formCount & (1 << 32)){
      $this->formCount = rand(0, 0xFFFFFFFF);
     }
  }

    public function onPacketReceived(DataPacketReceiveEvent $ev) : void {
     $pk = $ev->getPacket();
     if($pk instanceof ModalFormResponsePacket){
      $player = $ev->getPlayer();
      $formId = $pk->formId;
      $data = json_decode($pk->formData, true);
      if(isset($this->forms[$formId])){
       $form = $this->forms[$formId];
       if(!$form->isRecipient($player)){
        return;
       }
       $callable = $form->getCallable();
       if(!is_array($data)){
        $data = [$data];
       }
       if($callable !== null) {
        $callable($ev->getPlayer(), $data);
       }
       unset($this->forms[$formId]);
       $ev->setCancelled();
       }
    }
 }

    public function onPlayerQuit(PlayerQuitEvent $ev) {
     $player = $ev->getPlayer();
     foreach ($this->forms as $id => $form) {
      if($form->isRecipient($player)) {
       unset($this->forms[$id]);
       break;
      }
   }
}

    public function RedeemMenu($player){
     if($player instanceof Player){
      $form = $this->createCustomForm(function(Player $player, array $data){
      $result = $data[0];
      if($result != null){
       if($this->codeExists($this->giftcode, $result)) {
        if(!($this->codeExists($this->used, $result))) {
         $this->addCode($this->used, $result);
         case 4:
          $player->sendMessage($file->get("Getting-rewarded") §r§f $file->get("Reward")");
          $this->eco->addMoney($player->getName(), $file->get("Reward"));
          break;
         default:
          $player->sendMessage("$file->get("Error") §r§f!");
          break;
        }
     }else{
       $player->sendMessage("$file->get("Already-used") §r§f!");
        return true;
       }
    }else{
      $player->sendMessage("$file->get("Not-found") §r§f!");
      return true;
     }
  }else{
    $player->sendMessage("$file->get("No-input") §r§f!");
    return true;
   }
});
$form->setTitle("$file->get("Ui-title");
$form->addInput("$file->get("Ui-text");
$form->sendToPlayer($player);
}
}

    public static function getInstance() {
     return $this;
    }

    public function generateCode() {
     $characters = $file->get("Characters");
     $charactersLength = strlen($characters);
     $length = $file->get("Code-length");
     $randomString = '2019';
     for ($i = 0; $i < $length; $i++) {
      $randomString .= $characters[rand(0, $charactersLength - 1)];
     }
     $this->addCode($this->giftcode, $randomString);
     return $randomString;
     }

     public function codeExists($file, $code) {
      $query = $file->query("SELECT * FROM code WHERE code='$code';");
      $ar = $query->fetchArray(SQLITE3_ASSOC);
      if(!empty($ar)) {
       return true;
      } else {
         return false;
        }
     }

    public function addCode($file, $code) {
     $stmt = $file->prepare("INSERT OR REPLACE INTO code (code) VALUES (:code);");
     $stmt->bindValue(":code", $code);
     $stmt->execute();
    }

    public function onCommand(CommandSender $player, Command $command, string $label, array $args): bool{
     switch($command->getName()){
      case "gencode";
       if($player->isOp()) {
        $code = $this->generateCode();
        $player->sendMessage($file->get("Code-created") . $code);
       }else{
         $player->sendMessage($file->get("Not-allowed");
        }
        break;

      case "redeem";
       if($player instanceof Player){
        $this->RedeemMenu($player);
       }else{
         $player->sendMessage("§f§l[§r§eGiftCode§r§f§l]§r§6§o Please use this command ingame §r§f!");
        }
     }
     return true;
     }
  }
