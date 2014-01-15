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
   * @param $attacker
   * @param $defender
   * @return mixed
   */
  public static solve($attacker = array(), $defender = array()) {
    $return = FALSE;
    if (self::validateOpponent($attacker) && self::validateOpponent($defender)) {
      $rounds = array();

      // calculate chance to hit (cth) for attacker and defender, this will be a percent rate
      $attacker['cth'] = (($attacker['attack'] - $defender['defense']) / $attacker['attack']) * 100;
      $defender['cth'] = (($defender['attack'] - $attacker['defense']) / $defender['attack']) * 100;

      // no negative values allowed, otherwise this fighting system won't work
      if ($attacker['cth'] < 0) $attacker['cth'] = 0;
      if ($defender['cth'] < 0) $defender['cth'] = 0;

      // if both cth equal zero, the fighters won't be able to hit each other, so let's just return
      if ($attacker['cth'] == 0 && $defender['cth'] == 0) {
        $result = self::RPGC_BATTLE_DRAW;
      }
      else {
        // calculate damage attacker and defender will be able to deal to each other
        $attacker['dmg_rate'] = (($attacker['damage'] - $defender['armor']) / $attacker['damage']) * 100;
        $defender['dmg_rate'] = (($defender['damage'] - $attacker['armor']) / $defender['damage']) * 100;

        // same as above
        if ($attacker['dmg_rate'] < 0) $attacker['dmg_rate'] = 0;
        if ($defender['dmg_rate'] < 0) $defender['dmg_rate'] = 0;

        // if both fighters can't deal any damage, it might be better to just break here
        if ($attacker['dmg_rate'] == 0 && $defender['dmg_rate'] == 0) {
          $result = RPGC_BATTLE_DRAW;
        }
        else {
          // to determin when the fight is over, both fighters get a fake health bar
          $attacker['health'] = $defender['health'] = self::RPGC_BATTLE_INIT_HEALTH;

          // now both fighters are prepared, let's begin the fighting
          while ($attacker['health'] > 0 && $defender['health'] > 0) {
            $round = array();

            // the attacker started the battle, so he begins
            $round['attacker_roll'] = rand(0, 100);

            // if the throw is smaller then the chance to hit, he hits the defender
            if ($round['attacker_roll'] <= $attacker['cth']) {
              $round['attacker_damage'] = rand(0, $attacker['dmg_rate']);
              $defender['health'] -= $round['attacker_damage'];
            }

            // if the defender is still alive, it's his turn now
            if ($attacker['dmg_rate'] > 0 && $defender['health'] > 0) {
              $round['defender_roll'] = rand(0, 100);
              if ($round['defender_roll'] <= $defender['cth']) {
                $round['defender_damage'] = rand(0, $defender['dmg_rate']);
                $attacker['health'] -= $round['defender_damage'];
              }
            }

            $round['attacker_health'] = $attacker['health'];
            $round['defender_health'] = $defender['health'];

            array_push($rounds, $round);
          }

          // check who won this fight
          if ($attacker['health'] <= 0) {
            $result = self::RPGC_BATTLE_DEF_WINS;
          }
          else {
            $result = self::RPGC_BATTLE_ATT_WINS;
          }
        }
      }

      // Gather information for return.
      $return = array(
        'attacker' => $attacker,
        'defender' => $defender,
        'result'   => $result,
        'rounds'   => $rounds,
      );
    }

    return $return;
  }
}