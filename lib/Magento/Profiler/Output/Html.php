<?php
/**
 * Maho
 *
 * @category   Magento
 * @package    Magento_Profiler
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2022 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Class that represents profiler output in Html format
 */
class Magento_Profiler_Output_Html extends Magento_Profiler_OutputAbstract
{
    /**
     * Display profiling results
     */
    #[\Override]
    public function display()
    {
        $out  = '<table border="1" cellspacing="0" cellpadding="2">';
        $out .= '<caption>' . $this->_renderCaption() . '</caption>';
        $out .= '<tr>';
        foreach (array_keys($this->_getColumns()) as $columnLabel) {
            $out .= '<th>' . $columnLabel . '</th>';
        }
        $out .= '</tr>';
        foreach ($this->_getTimers() as $timerId) {
            $out .= '<tr>';
            foreach ($this->_getColumns() as $columnId) {
                $out .= '<td title="' . $timerId . '">' . $this->_renderColumnValue($timerId, $columnId) . '</td>';
            }
            $out .= '</tr>';
        }
        $out .= '</table>';

        echo $out;
    }

    /**
     * Render timer id column value
     *
     * @param string $timerId
     * @return string
     */
    #[\Override]
    protected function _renderTimerId($timerId)
    {
        $nestingSep = preg_quote(Magento_Profiler::NESTING_SEPARATOR, '/');
        return preg_replace('/.+?' . $nestingSep . '/', '&middot;&nbsp;&nbsp;', $timerId);
    }
}