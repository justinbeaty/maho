<?php
/**
 * Maho
 *
 * @package    Varien_Convert
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2022-2023 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Varien_Convert_Adapter_Http extends Varien_Convert_Adapter_Abstract
{
    #[\Override]
    public function load()
    {
        if (!$_FILES) {
            ?>
<form method="POST" enctype="multipart/form-data">
File to upload: <input type="file" name="io_file"/> <input type="submit" value="Upload"/>
</form>
            <?php
            exit;
        }
        if (!empty($_FILES['io_file']['tmp_name'])) {
            $this->setData(file_get_contents($_FILES['io_file']['tmp_name']));
        }
        return $this;
    }

    #[\Override]
    public function save()
    {
        if ($this->getVars()) {
            foreach ($this->getVars() as $key => $value) {
                header($key . ': ' . $value);
            }
        }
        echo $this->getData();
        return $this;
    }

    // experimental code
    public function loadFile()
    {
        if (!$_FILES) {
            ?>
<form method="POST" enctype="multipart/form-data">
File to upload: <input type="file" name="io_file"/> <input type="submit" value="Upload"/>
</form>
            <?php
            exit;
        }
        if (!empty($_FILES['io_file']['tmp_name'])) {
            //$this->setData(file_get_contents($_FILES['io_file']['tmp_name']));
            $uploader = new Varien_File_Uploader('io_file');
            $uploader->setAllowedExtensions(['csv','xml']);
            $path = Mage::app()->getConfig()->getTempVarDir() . '/import/';
            $uploader->save($path);
            if ($uploadFile = $uploader->getUploadedFileName()) {
                $session = Mage::getModel('dataflow/session');
                $session->setCreatedDate(date(Varien_Db_Adapter_Pdo_Mysql::TIMESTAMP_FORMAT));
                $session->setDirection('import');
                $session->setUserId(Mage::getSingleton('admin/session')->getUser()->getId());
                $session->save();
                $sessionId = $session->getId();
                $newFilename = 'import_' . $sessionId . '_' . $uploadFile;
                rename($path . $uploadFile, $path . $newFilename);
                $session->setFile($newFilename);
                $session->save();
                $this->setData(file_get_contents($path . $newFilename));
                Mage::register('current_dataflow_session_id', $sessionId);
            }
        }
        return $this;
    }
}
