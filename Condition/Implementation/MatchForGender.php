<?php
/**********************************************************************************/
/*                                                                                */
/*      Thelia	                                                                  */
/*                                                                                */
/*      Copyright (c) OpenStudio                                                  */
/*      email : info@thelia.net                                                   */
/*      web : http://www.thelia.net                                               */
/*                                                                                */
/*      This program is free software; you can redistribute it and/or modify      */
/*      it under the terms of the GNU General Public License as published by      */
/*      the Free Software Foundation; either version 3 of the License             */
/*                                                                                */
/*      This program is distributed in the hope that it will be useful,           */
/*      but WITHOUT ANY WARRANTY; without even the implied warranty of            */
/*      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the             */
/*      GNU General Public License for more details.                              */
/*                                                                                */
/*      You should have received a copy of the GNU General Public License         */
/*	    along with this program. If not, see <http://www.gnu.org/licenses/>.      */
/*                                                                                */
/**********************************************************************************/

namespace ConditionMatchForGender\Condition\Implementation;

use Thelia\Condition\Implementation\ConditionAbstract;
use Thelia\Condition\Operators;
use Thelia\Exception\InvalidConditionOperatorException;
use Thelia\Exception\InvalidConditionValueException;


/**
 * Allow filter by gender (man or woman)
 *
 * @package Condition
 * @author  Guillaume MOREL <gmorel@openstudio.fr>
 *
 */
class MatchForGender extends ConditionAbstract
{
    /** Condition 1st parameter : gender */
    CONST INPUT1 = 'gender';

    CONST GENDER_MAN = 'man';
    CONST GENDER_WOMAN = 'woman';

    /** @var string Service Id from Resources/config.xml  */
    protected $serviceId = 'thelia.condition.match_for_gender';

    /** @var array Available Operators (Operators::CONST) */
    protected $availableOperators = array(
        self::INPUT1 => array(
            Operators::EQUAL,
        )
    );

    /**
     * Check validators relevancy and store them
     *
     * @param array $operators Operators the Admin set in BackOffice
     * @param array $values    Values the Admin set in BackOffice
     *
     * @throws \InvalidArgumentException
     * @return $this
     */
    public function setValidatorsFromForm(array $operators, array $values)
    {
        $this->setValidators(
            $operators[self::INPUT1],
            $values[self::INPUT1]
        );

        return $this;
    }

    /**
     * Check validators relevancy and store them
     *
     * @param string $genderOperator Gender Operator ex <
     * @param int    $genderValue    Gender set to meet condition
     *
     * @throws \Thelia\Exception\InvalidConditionValueException
     * @throws \Thelia\Exception\InvalidConditionOperatorException
     * @return $this
     */
    protected function setValidators($genderOperator, $genderValue)
    {
        // We first test if the operator given by the admin is legit
        // ie. in our case if it is Operators::EQUAL (==)
        $isOperator1Legit = $this->isOperatorLegit(
            $genderOperator,
            $this->availableOperators[self::INPUT1]
        );
        // If not we throw an exception wich will display an error
        // During the condition saving.
        if (!$isOperator1Legit) {
            throw new InvalidConditionOperatorException(
                get_class(), 'gender'
            );
        }

        // We then check if the admin set correctly the parameter value
        // If value selected is either self::GENDER_MAN (man)
        // or self::GENDER_WOMAN (woman)
        if (!in_array($genderValue, array(self::GENDER_MAN, self::GENDER_WOMAN))) {
            throw new InvalidConditionValueException(
                get_class(), 'gender'
            );
        }

        // We then assign set entered operators and values
        $this->operators = array(
            self::INPUT1 => $genderOperator,
        );
        $this->values = array(
            self::INPUT1 => $genderValue,
        );

        return $this;
    }

    /**
     * Test if Customer meets conditions
     *
     * @return bool
     */
    public function isMatching()
    {
        // We retrieve current Customer title
        // 1 M
        // 2 Mrs
        // 3 Miss
        $titleId = $this->facade->getCustomer()->getTitleId();

        // We match the customer title to our stored parameter
        // 1 => self::GENDER_MAN (man)
        // 2 and 3 => self::GENDER_WOMAN (woman)
        $toCheck = self::GENDER_WOMAN;
        if ($titleId == 1) {
            $toCheck = self::GENDER_MAN;
        }

        // Is Customer the title gender matching
        // the gender set in this Condition ?
        $condition = $this->conditionValidator->variableOpComparison(
            $toCheck,
            $this->operators[self::INPUT1],
            $this->values[self::INPUT1]
        );

        if ($condition) {
            return true;
        }

        return false;
    }

    /**
     * Get I18n name
     *
     * @return string
     */
    public function getName()
    {
        return $this->translator->trans(
            'By Customer gender',
            array(),
            'condition'
        );
    }

    /**
     * Get I18n tooltip
     * Explain in detail what the Condition checks
     *
     * @return string
     */
    public function getToolTip()
    {
        $toolTip = $this->translator->trans(
            'If customer is a man or a woman',
            array(),
            'condition'
        );

        return $toolTip;
    }

    /**
     * Get I18n summary
     * Explain briefly the condition with given values
     *
     * @return string
     */
    public function getSummary()
    {
        $toolTip = $this->translator->trans(
            'If customer <strong>is a %gender%</strong>',
            array(
                '%gender%' => $this->values[self::INPUT1]
            ),
            'condition'
        );

        return $toolTip;
    }

    /**
     * Generate inputs ready to be drawn
     *
     * @return array
     */
    protected function generateInputs()
    {
        return array(
            self::INPUT1 => array(
                'availableOperators' => $this->availableOperators[self::INPUT1],
                'value' => '',
                'selectedOperator' => ''
            )
        );
    }

    /**
     * Draw the input displayed in the BackOffice
     * allowing Admin to set its Coupon Conditions
     *
     * @return string HTML string
     */
    public function drawBackOfficeInputs()
    {
        $labelOnlyForMen = $this->translator->trans(
            'Available only if a Customer is a man',
            array(),
            'condition'
        );
        $labelOnlyForWomen = $this->translator->trans(
            'Available only if a Customer is a woman',
            array(),
            'condition'
        );

        $checkedWoman = $checkedMan = '';
        if (isset($this->operators) && isset($this->operators[self::INPUT1])) {
            if ($this->operators[self::INPUT1] == self::GENDER_WOMAN) {
                $checkedWoman = 'checked';
            } else {
                $checkedMan = 'checked';
            }
        }

        $html = '
                <div id="condition-add-operators-values" class="form-group col-md-6">
                    <input type="hidden" id="' . self::INPUT1 . '-operator" name="' . self::INPUT1 . '[operator]" value="==" />
                    <div class="row radio">
                        <div class="input-group col-lg-10">
                            <label>
                                <input type="radio" name="' . self::INPUT1 . '[value]" value="' . self::GENDER_WOMAN . '" ' . $checkedWoman . '>
                                ' . $labelOnlyForWomen . '
                            </label>
                        </div>
                    </div>
                    <div class="row radio">
                        <div class="input-group col-lg-10">
                            <label>
                                <input type="radio" name="' . self::INPUT1 . '[value]" value="' . self::GENDER_MAN . '" ' . $checkedMan . '>
                                ' . $labelOnlyForMen . '
                            </label>
                        </div>
                    </div>
                </div>
            ';
        return $html;
    }
}

