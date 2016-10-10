<?php

/**
 * @author     Branko Wilhelm <branko.wilhelm@gmail.com>
 * @link       http://www.z-index.net
 * @copyright  (c) 2016 Branko Wilhelm
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @var        array $raids
 * @var        stdClass $module
 * @var        Joomla\Registry\Registry $params
 */

defined('_JEXEC') or die;

JHtml::_('jquery.framework');

JFactory::getDocument()->addScript('media/' . $module->module . '/js/default.js');
JFactory::getDocument()->addStyleSheet('media/' . $module->module . '/css/default.css');
?>
<?php if ($params->get('ajax')) : ?>
    <div class="mod_wow_raid_progress_legion ajax"></div>
<?php else: ?>
    <div class="mod_wow_raid_progress_legion">
        <?php foreach ($raids as $zoneId => $zone) : ?>
            <ul class="z<?php echo $zoneId; ?>">
                <li class="header">
                    <span class="p" style="width:<?php echo $zone['stats']['percent']; ?>%;"></span>
                    <?php echo JText::_('MOD_WOW_RAID_PROGRESS_LEGION_ZONE_' . $zoneId); ?>
                    <span class="k" title="<?php echo $zone['stats']['percent']; ?>%"><?php echo JText::sprintf('MOD_WOW_RAID_PROGRESS_LEGION_MODE_' . strtoupper($zone['stats']['mode']), $zone['stats']['kills'], $zone['stats']['bosses']); ?></span>
                </li>
                <li class="npcs<?php echo ($zone['collapsed'] == true) ? ' open' : ''; ?>">
                    <ul>
                        <?php foreach ($zone['npcs'] as $npc => $data) : ?>
                            <li class="npc">
                                <?php echo JHtml::_('link', $data['link'], JText::_('MOD_WOW_RAID_PROGRESS_LEGION_NPC_' . $npc), array('target' => '_blank')); ?>
                                <span class="<?php if ($data['mythic'] === true) echo ' mythic'; elseif ($data['heroic'] === true) echo ' heroic';
                                elseif ($data['normal'] === true) echo ' normal'; ?>"> </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </li>
            </ul>
        <?php endforeach; ?>
    </div>
<?php endif; ?>