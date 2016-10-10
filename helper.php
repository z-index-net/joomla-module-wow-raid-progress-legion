<?php

/**
 * @author     Branko Wilhelm <branko.wilhelm@gmail.com>
 * @link       http://www.z-index.net
 * @copyright  (c) 2016 Branko Wilhelm
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

defined('_JEXEC') or die;

class ModWowRaidProgressLegionHelper extends WoWModuleAbstract
{
    /**
     * @var array
     */
    private $raids = array(
        // The Emerald Nightmare
        8026 => array(
            'link' => 'zone/the-emerald-nightmare/',
            'stats' => array('kills' => 0, 'mode' => 'normal'),
            'npcs' => array(
                // Nythendra
                102672 => array(
                    'link' => 'zone/the-emerald-nightmare/nythendra',
                    'normal' => 0,
                    'heroic' => 0,
                    'mythic' => 10821
                ),
                // Il'gynoth
                105393 => array(
                    'link' => 'zone/the-emerald-nightmare/ilgynoth-the-heart-of-corruption',
                    'normal' => 0,
                    'heroic' => 0,
                    'mythic' => 10823
                ),
                // Elerethe Renferal
                106087 => array(
                    'link' => 'zone/the-emerald-nightmare/elerethe-renferal',
                    'normal' => 0,
                    'heroic' => 0,
                    'mythic' => 10822
                ),
                // Ursoc
                26633 => array(
                    'link' => 'zone/the-emerald-nightmare/ursoc',
                    'normal' => 0,
                    'heroic' => 0,
                    'mythic' => 0
                ),
                // Dragons of Nightmare
                39407 => array(
                    'link' => 'zone/the-emerald-nightmare/dragons-of-nightmare',
                    'normal' => 0,
                    'heroic' => 0,
                    'mythic' => 10825
                ),
                // Cenarius
                113534 => array(
                    'link' => 'zone/the-emerald-nightmare/cenarius',
                    'normal' => 0,
                    'heroic' => 0,
                    'mythic' => 10826
                ),
                // Xavius
                102206 => array(
                    'link' => 'zone/the-emerald-nightmare/xavius',
                    'normal' => 0,
                    'heroic' => 0,
                    'mythic' => 10827
                ),
            ),
        ),
    );

    /**
     * @return array
     */
    protected function getInternalData()
    {
        if ($this->params->module->get('mode') == 'auto') {
            try {
                $result = WoW::getInstance()->getAdapter('WoWAPI')->getData('members');
            } catch (Exception $e) {
                return $e->getMessage();
            }

            if (isset($result->body->achievements) && is_object($result->body->achievements)) {
                $this->checkNormal($result->body->achievements);
            }

            if ((in_array('mythic', (array)$this->params->module->get('difficulty')) || in_array('mythic', (array)$this->params->module->get('difficulty'))) && $this->params->module->get('ranks')) {
                $this->checkHeroicAndMythic($result->body->members);
            }
        }

        if ($hidden = $this->params->module->get('hide')) {
            foreach ($hidden as $hide) {
                unset($this->raids[$hide]);
            }
        }

        $this->adjustments();

        // at last replace links and count mode-kills
        foreach ($this->raids as $zoneId => &$zone) {
            $zone['link'] = $this->link($zone['link'], $zoneId);
            $mythic = $heroic = $normal = 0;
            foreach ($zone['npcs'] as $npcId => &$npc) {
                $npc['link'] = $this->link($npc['link'], $npcId, true);
                if ($npc['mythic'] === true) {
                    $mythic++;
                }
                if ($npc['heroic'] === true) {
                    $heroic++;
                }
                if ($npc['normal'] === true) {
                    $normal++;
                }
            }

            if ($normal > 0) {
                $zone['stats']['kills'] = $normal;
            }

            if ($heroic > 0) {
                $zone['stats']['kills'] = $heroic;
                $zone['stats']['mode'] = 'heroic';
            }

            if ($mythic > 0) {
                $zone['stats']['kills'] = $mythic;
                $zone['stats']['mode'] = 'mythic';
            }

            $zone['collapsed'] = in_array($zoneId, (array)$this->params->module->get('collapsed'));

            $zone['stats']['bosses'] = count($zone['npcs']);
            $zone['stats']['percent'] = round(($zone['stats']['kills'] / $zone['stats']['bosses']) * 100);
        }

        return $this->raids;
    }

    private function checkNormal(stdClass $achievements)
    {
        foreach ($this->raids as &$zone) {
            foreach ($zone['npcs'] as &$npc) {
                $npc['normal'] = in_array($npc['normal'], $achievements->criteria);
            }
        }
    }

    private function checkHeroicAndMythic(array &$members)
    {
        $heroicIds = $this->getHeroicIDs();
        $mythicIds = $this->getMythicIDs();
        foreach ($members as &$member) {
            if (in_array($member->rank, $this->params->module->get('ranks'))) {
                $member->achievements = $this->loadMember($member->character->name, $member->character->realm);
                if (!empty($member->achievements)) {
                    foreach ($heroicIds as $id => $zoneNpc) {
                        list ($npc, $zone) = explode(':', $zoneNpc, 2);
                        if (in_array($id, $member->achievements->achievementsCompleted)) {
                            $this->raids[$zone]['npcs'][$npc]['heroic']++;
                        }
                    }
                    foreach ($mythicIds as $id => $zoneNpc) {
                        list ($npc, $zone) = explode(':', $zoneNpc, 2);
                        if (in_array($id, $member->achievements->achievementsCompleted)) {
                            $this->raids[$zone]['npcs'][$npc]['mythic']++;
                        }
                    }
                }
            }
        }

        foreach ($this->raids as &$zone) {
            foreach ($zone['npcs'] as &$npc) {
                $npc['heroic'] = (bool)($npc['heroic'] >= $this->params->module->get('successful', 5));
                $npc['mythic'] = (bool)($npc['mythic'] >= $this->params->module->get('successful', 5));
            }
        }
    }

    private function getHeroicIDs()
    {
        $result = array();
        foreach ($this->raids as $zoneId => &$zone) {
            foreach ($zone['npcs'] as $npc => &$modes) {
                $result[$modes['heroic']] = $npc . ':' . $zoneId;
                $modes['heroic'] = 0;
            }
        }

        return $result;
    }

    private function getMythicIDs()
    {
        $result = array();
        foreach ($this->raids as $zoneId => &$zone) {
            foreach ($zone['npcs'] as $npc => &$modes) {
                $result[$modes['mythic']] = $npc . ':' . $zoneId;
                $modes['mythic'] = 0;
            }
        }

        return $result;
    }

    /**
     * @param $member
     * @param $realm
     *
     * @return bool|string
     */
    private function loadMember($member, $realm)
    {
        try {
            $result = WoW::getInstance()->getAdapter('WoWAPI')->getMember($member, $realm);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        if (!is_object($result->body) || !isset($result->body->achievements)) {
            return false;
        }

        return $result->body->achievements;
    }

    private function adjustments()
    {
        foreach ($this->raids as $zoneId => &$zone) {
            foreach ($zone['npcs'] as $npcId => &$npc) {
                if ($npc['mythic'] === true || $npc['heroic'] === true || $npc['normal'] === true) {
                    continue;
                }
                switch ($this->params->module->get('adjust_' . $npcId)) {
                    default:
                        continue;
                        break;

                    case 'no':
                        $npc['normal'] = false;
                        $npc['heroic'] = false;
                        $npc['mythic'] = false;
                        break;

                    case 'normal':
                        $npc['normal'] = true;
                        break;

                    case 'heroic':
                        $npc['heroic'] = true;
                        break;

                    case 'mythic':
                        $npc['mythic'] = true;
                        break;
                }
            }
        }
    }

    /**
     * @param $link
     * @param $id
     * @param bool $npc
     *
     * @return string
     */
    private function link($link, $id, $npc = false)
    {
        if ($npc) {
            $sites['battle.net'] = 'http://' . $this->params->global->get('region') . '.battle.net/wow/' . $this->params->global->get('locale') . '/' . $link;
            $sites['wowhead.com'] = 'http://' . $this->params->global->get('locale') . '.wowhead.com/npc=' . $id;
        } else {
            $sites['battle.net'] = 'http://' . $this->params->global->get('region') . '.battle.net/wow/' . $this->params->global->get('locale') . '/' . $link;
            $sites['wowhead.com'] = 'http://' . $this->params->global->get('locale') . '.wowhead.com/zone=' . $id;
        }

        return $sites[$this->params->global->get('link')];
    }
}
