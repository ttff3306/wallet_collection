<?php

namespace app\admin\controller\wallet;

use app\common\controller\Backend;
use app\common\facade\Mnemonic;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

/**
 * 助记词导入管理
 *
 * @icon fa fa-circle-o
 */
class ImportMnemonic extends Backend
{
    
    /**
     * ImportMnemonic模型对象
     * @var \app\admin\model\wallet\ImportMnemonic
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\wallet\ImportMnemonic;

    }

    /**
     * 添加
     * @return mixed
     * @throws \Exception
     * @author Bin
     * @time 2023/7/31
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post('row/a');
            if ($params) {
                //获取助记词
                $mnemonic = $params['mnemonic'];
                $result = Mnemonic::importWalletByMnemonic($mnemonic);
                if (!$result) {
                    $this->error('导入失败');
                }else{
                    $this->success('导入成功');
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }

        return $this->view->fetch();
    }

    /**
     * 文本导入
     * @return void
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     * @author Bin
     * @time 2023/7/26
     */
    public function import()
    {
        ini_set('memory_limit', "512M");
        set_time_limit(500);
        $file = $this->request->request('file');
        if (!$file) {
            $this->error(__('Parameter %s can not be empty', 'file'));
        }
        $filePath = app()->getRootPath().DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.$file;
        if (!is_file($filePath)) {
            $this->error(__('No results were found'));
        }
        //实例化reader
        $ext = pathinfo($filePath, PATHINFO_EXTENSION);
        if (!in_array($ext, ['csv', 'xls', 'xlsx'])) {
            $this->error(__('Unknown data format'));
        }
        if ($ext === 'csv') {
            $file     = fopen($filePath, 'r');
            $filePath = tempnam(sys_get_temp_dir(), 'import_csv');
            $fp       = fopen($filePath, 'w');
            $n        = 0;
            while ($line = fgets($file)) {
                $line     = rtrim($line, "\n\r\0");
                $encoding = mb_detect_encoding($line, ['utf-8', 'gbk', 'latin1', 'big5']);
                if ($encoding != 'utf-8') {
                    $line = mb_convert_encoding($line, 'utf-8', $encoding);
                }
                if ($n == 0 || preg_match('/^".*"$/', $line)) {
                    fwrite($fp, $line."\n");
                } else {
                    fwrite($fp, '"'.str_replace(['"', ','], ['""', '","'], $line)."\"\n");
                }
                $n++;
            }
            fclose($file) || fclose($fp);

            $reader = new Csv();
        } elseif ($ext === 'xls') {
            $reader = new Xls();
        } else {
            $reader = new Xlsx();
        }
        //加载文件
        $insert = [];

        try {
            if (!$PHPExcel = $reader->load($filePath)) {
                $this->error(__('Unknown data format'));
            }
            $currentSheet    = $PHPExcel->getSheet(0);  //读取文件中的第一个工作表
            $allColumn       = $currentSheet->getHighestDataColumn(); //取得最大的列号
            $allRow          = $currentSheet->getHighestRow(); //取得一共有多少行
            $maxColumnNumber = Coordinate::columnIndexFromString($allColumn);
            for ($currentRow = 1; $currentRow <= $allRow; $currentRow++) {
                for ($currentColumn = 1; $currentColumn <= $maxColumnNumber; $currentColumn++) {
                    $val      = $currentSheet->getCellByColumnAndRow($currentColumn, $currentRow)->getValue();
                    $insert[] = is_null($val) ? '' : $val;
                }
            }
        } catch (Exception $exception) {
            $this->error($exception->getMessage());
        }
        if (empty($insert)) {
            $this->error(__('No rows were updated'));
        }

        $success_num = 0;
        try {
            foreach ($insert as $mnemonic){
                if (empty($mnemonic) || !is_string($mnemonic)) continue;
//                $result = Mnemonic::importWalletByMnemonic($mnemonic);
                publisher('asyncImportWalletByMnemonic', ['mnemonic' => $mnemonic], 0, 'M');
                $success_num++;
            }
        } catch (\PDOException $exception) {
            $msg = $exception->getMessage();
            if (preg_match("/.+Integrity constraint violation: 1062 Duplicate entry '(.+)' for key '(.+)'/is", $msg,
                $matches)) {
                $msg = "导入失败，包含【{$matches[1]}】的记录已存在";
            }
            $this->error($msg);
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }

        $this->success("本次成功导入{$success_num}条数据");
    }
}
