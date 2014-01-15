<?php
/**
 * RpgCreator Battle Script
 * A generic battle calculation class for use in rpg games.
 *
 * @copyright Christian Hanne <mail@christianhanne.de>
 * @link https://github.com/christianhanne/rpgc_battle
 */
class RpgBattle {
  /**
   * Attacker kills the defender. Yeah! This is the return value.
   */
  const $RPGC_BATTLE_ATT_WINS = 1;

  /**
   * Return value, if none of the characters could win a battle. Bah, lame!
   */
  const $RPGC_BATTLE_DRAW = 0;

  /**
   * Defender successfully defends himself, making his name count.
   */
  const $RPGC_BATTLE_DEF_WINS = -1;

  /**
   * Starting health of each character in the battle.
   * This is meant to be seen as a percentage value.
   */
  const $RPGC_BATTLE_INIT_HEALTH = 100;

  /**
   * Validates a battle's opponent.
   *
   * @param array $array
   *   A battle opponent representated by an array
   *   consisting of four values: attack, defense,
   *   armor & damage.
   * @return boolean
   */
  private static validateOpponent($array) {
    $valid = TRUE;
    if (!isset($array['attack']) || !is_numeric($array['attack'])) {
      $valid = FALSE;
    }
    else if (!isset($array['defense']) || !is_numeric($array['defense'])) {
      $valid = FALSE;
    }
    else if (!isset($array['armor']) || !is_numeric($array['armor'])) {
      $valid = FALSE;
    }
    else if (!isset($array['damage']) || !is_numeric($array['damage'])) {
      $valid = FALSE;
    }
    else if (isset($array['health']) && !is_numeric($array['health'])) {
      $valid = FALSE;
    }

    return $valid;
  }

  /**
   * Solves a battle between two opponents.
   *
   * @param array $att
   *   An array that represents the attacker.
   *   Attacker gets to strike first in a battle.
   * @param array $def
   *   An array that represents the defender.
   * @return mixed
   *   Returns either an array or FALSE.
   */
  public static solve($att = array(), $def = array()) {
    $return = FALSE;
    if (self::validateOpponent($att) && self::validateOpponent($def)) {
      $rounds = array();

      // calculate chance to hit (cth) for attacker and defender, this will be a percent rate
      $att['cth'] = (($att['attack'] - $def['defense']) / $att['attack']) * 100;
      $def['cth'] = (($def['attack'] - $att['defense']) / $def['attack']) * 100;

      // no negative values allowed, otherwise this fighting system won't work
      $att['cth'] = $att['cth'] > 0 ? $att['cth'] : 0;
      $def['cth'] = $def['cth'] > 0 ? $def['cth'] : 0;

      // if both cth equal zero, the fighters won't be able to hit each other, so let's just return
      if ($att['cth'] == 0 && $def['cth'] == 0) {
        $result = self::RPGC_BATTLE_DRAW;
      }
      else {
        // calculate damage attacker and defender will be able to deal to each other
        $att['dmg_rate'] = (($att['damage'] - $def['armor']) / $att['damage']) * 100;
        $def['dmg_rate'] = (($def['damage'] - $att['armor']) / $def['damage']) * 100;

        // same as above
        $att['dmg_rate'] = $att['dmg_rate'] > 0 ? $att['dmg_rate'] : 0;
        $def['dmg_rate'] = $def['dmg_rate'] > 0 ? $def['dmg_rate'] : 0;

        // if both fighters can't deal any damage, it might be better to just break here
        if ($att['dmg_rate'] == 0 && $def['dmg_rate'] == 0) {
          $result = RPGC_BATTLE_DRAW;
        }
        else {
          // to determin when the fight is over, both fighters get a fake health bar
          $att['health'] = isset($att['health']) ? $att['health'] : self::RPGC_BATTLE_INIT_HEALTH;
          $def['health'] = isset($def['health']) ? $def['health'] : self::RPGC_BATTLE_INIT_HEALTH;

          // now both fighters are prepared, let's begin the fighting
          while ($att['health'] > 0 && $def['health'] > 0) {
            $round = array();

            // the attacker started the battle, so he begins
            $round['attacker_roll'] = rand(0, 100);

            // if the throw is smaller then the chance to hit, he hits the defender
            if ($round['attacker_roll'] <= $att['cth']) {
              $round['attacker_damage'] = rand(0, $att['dmg_rate']);
              $def['health'] -= $round['attacker_damage'];
            }

            // if the defender is still alive, it's his turn now
            if ($att['dmg_rate'] > 0 && $def['health'] > 0) {
              $round['defender_roll'] = rand(0, 100);
              if ($round['defender_roll'] <= $def['cth']) {
                $round['defender_damage'] = rand(0, $def['dmg_rate']);
                $att['health'] -= $round['defender_damage'];
              }
            }

            $round['attacker_health'] = $att['health'];
            $round['defender_health'] = $def['health'];

            array_push($rounds, $round);
          }

          // check who won this fight
          if ($att['health'] <= 0) {
            $result = self::RPGC_BATTLE_DEF_WINS;
          }
          else {
            $result = self::RPGC_BATTLE_ATT_WINS;
          }
        }
      }

      // Gather information for return.
      $return = array(
        'attacker' => $att,
        'defender' => $def,
        'result'   => $result,
        'rounds'   => $rounds,
      );
    }

    return $return;
  }
}