<?php

/**
 * Created by PhpStorm.
 * User: Kevin
 * Date: 23/06/2017
 * Time: 12:02
 */
class HandlerFiles{

    private $extensionsAccepted = array();

    private $dataReturn;

    /**
     * Função para definir filtro das extenções dos arquivos
     * @access public
     * @param array $extensionsAccepted
     * @return void
     */
    public function setExtensionsAccepted(Array $extensionsAccepted){
        foreach($extensionsAccepted as $format){
            $extensionsAccepted[] = strtoupper($format);
        }
        $this->extensionsAccepted = $extensionsAccepted;

    }

    /**
     * Função para imprimir texto para inserir no accept do input (files) do html
     * @access public
     * @return String
     */
    public function printExtensionsAccepted(){
        return ".".implode(', .', $this->extensionsAccepted);
    }

    /**
     * Função para limpar o caminho do arquivo ou pasta para a classe conseguir ler
     * @access public
     * @param String $path
     * @return String
     */
    public function clearPath($path){

        //Se no começo do Path, tiver barra, retira a barra
        if(substr($path, 0, 1) == '/'){
            $path[strpos($path, '/')]='';
        }

        //Enquanto tiver "../" no começo do Path, ele irá retirar
        while(strpos($path, '../') == 0 && strpos($path, '../') != null){
            $path = str_replace('../', '', $path);
        }

        //Monta o caminho, pela raiz do projeto
        $path = __DIR__.'/../../'.str_replace('../', '', $path);

        if(is_dir($path)){
            //Se no final do Path, não tiver Barra, adiciona a barra
            if(substr($path, -1) != '/'){
                $path .= '/';
            }
        }
        return $path;
    }

    /**
     * Função para retornar uma mensagem
     * @access public
     * @param boolean $error
     * @param int $errorCode
     * @param array $fields
     * @return void
     */
    public function returnMessage($error, $errorCode, $fields = array()){
        $errorCodes = array(
            0 => 'Requisição Feita com Sucesso!',
            1 => 'Não foi possivel remover o arquivo',
            2 => 'Arquivo não encontrado',
            3 => 'Não foi possivel criar a pasta',
            4 => 'Não há arquivos para adicionar',
            5 => 'Formato não aceito!',
            6 => 'Arquivo Muito Grande, Tamanho Maximo: '.ini_get('upload_max_filesize'),
            7 => 'Não foi possivel encontrar o arquivo',
            8 => 'Não foi possivel renomear o arquivo',
            9 => 'Não foi encontrado nenhuma arquivo',
            10 => 'Diretorio inválido',
            99 => 'Erro Desconhecido'
        );
        $array = array('error' => $error, 'errorCode' => $errorCode, 'message' => $errorCodes[$errorCode]);
        
        if($fields != null){
            foreach($fields as $name => $field){
                $array[$name] = $field;
            }
        }
        $this->dataReturn = $array;
    }

    /**
     * Função para retornar em json
     * @access public
     * @return string
     */
    public function getJson(){
        return json_encode($this->dataReturn);
    }
    /**
     * Função para retornar em Array
     * @access public
     * @return array
     */
    public function getArray(){
        return $this->dataReturn;
    }
    /**
     * Função para retornar apenas a mensagem
     * @access public
     * @return string
     */
    public function getMessage(){
        return $this->dataReturn['message'];
    }
    /**
     * Função para retornar apenas o codigo do erro
     * @access public
     * @return string
     */
    public function getErrorCode(){
        return $this->dataReturn['errorCode'];
    }
    /**
     * Função para retornar se tem error
     * @access public
     * @return boolean
     */
    public function hasErrors(){
        return $this->dataReturn['error'];
    }

    /**
     * Função para listagem de arquivos em uma pasta
     * @access public
     * @param string $path
     * @param boolean $justName
     * @return array
     */
    public function getAll($path, $justName = false){
        $files = array();
        $path = $this->clearPath($path);

        //Se for um diretorio
        if (is_dir($path)) {
            foreach (new DirectoryIterator($path) as $fileInfo) {
                if (is_null($this->extensionsAccepted)) {
                    if ($fileInfo->isFile()) {
                        $files[$fileInfo->getBasename()] = $fileInfo->getPath();
                    }
                } else {
                    if (in_array($fileInfo->getExtension(), $this->extensionsAccepted)) {
                        if ($fileInfo->isFile()) {
                            $files[$fileInfo->getBasename()] = $fileInfo->getPath();
                        }
                    }
                }

            }
            if (count($files) <= 0) {
                $this->returnMessage(true, 9);
            }

            //Voltar os arquivos já separados por um separador de string
            if ($justName) {
                $separatedFiles = array();
                foreach ($files as $nameFile => $pathFile) {
                    $name = explode('.', $nameFile);
                    $nameWithoutExtension = '';
                    //Monta o nome mesmo com pontos no meio e sem a extensão
                    for ($i = 0; $i < count($name); $i++) {
                        if ($i + 1 != count($name)) {
                            $nameWithoutExtension .= $name[$i];
                        }
                    }
                    $separatedFiles[$nameWithoutExtension] = array('nameWithExtension' => $nameFile, 'path' => $pathFile);
                }
                return $separatedFiles;
            }
            return $files;
        } else {
            $this->returnMessage(true, 10, array('path' => $path));
        }
        return null;
    }

    public function find($path, $comparator, $separator = false, $index = 0){
        $files = $this->getAll($path, true);
        if($files != false && is_array($files)){
            foreach($files as $fileName => $file){
                if($separator == false){
                    if($fileName == $comparator){
                        return $file;
                    }
                }else{
                    if(is_array($separator)){
                        $name = $fileName;
                        foreach($separator as $value){
                            $name = explode($value['separator'], $name);
                            $name = $name[$value['index']];
                        }
                        if($name[$index] == $comparator){
                            return $file;
                        }
                    }else{
                        $name = explode($separator, $fileName);
                        if ($name[$index] == $comparator) {
                            return $file;
                        }
                    }
                }
            }
        }
        $this->returnMessage(true, 9);
        return null;
    }

    public function add($path, $name){
        if(!is_dir($this->clearPath($path))){
            if(!mkdir($this->clearPath($path), 0755, true)){
                $this->returnMessage(true, 3);
            }
        }
        if(is_null($_FILES)){
            $this->returnMessage(true, 4);
        }

        foreach ($_FILES as $arq => $array) {
            if (strlen($array['name']) > 0) {

                $extension = explode('.', $array['name']);
                $extension = $extension[count($extension)-1];

                if(!is_null($this->extensionsAccepted)){
                    if(!in_array($extension, $this->extensionsAccepted)){
                        $this->returnMessage(true, 5);
                    }
                }

                $name = $name.".".strtolower($extension);

                if(move_uploaded_file($array['tmp_name'], $this->clearPath($path).$name)){
                    $this->returnMessage(false, 0, array('path' => $path.$name));
                    return true;
                }else{
                    switch($_FILES[$arq]["error"]){
                        case 1:
                            $code = 6;
                            break;
                        case 2:
                            $code = 6;
                            break;
                        case 4:
                            $code = 4;
                            break;
                        default:
                            $code = 99;
                    }
                    $this->returnMessage(true,  $code);
                }
            }
        }
        return false;
    }

    public function rename($path, $newName, $oldName){

        $path = $this->clearPath($path);
        $file = $this->find($path, $oldName);

        if($file == false){
            $this->returnMessage(true, 7);
            return false;
        }

        $extension = explode('.', $file);

        if(is_dir($path)){
            $file = $path . $file;
        }

        if (rename($file, $newName.$extension[1])){
            $this->returnMessage(false, 0, array('path' => $path.$newName.$extension[count($extension)-1]));
            return true;
        }else{
            $this->returnMessage(true, 8);
            return false;
        }
    }

    public function remove($path, $comparador, $separador = false, $indice = 0){
        $file = $this->find($path, $comparador, $separador, $indice);
        $path = $this->clearPath($path).$file;

        if (is_file($path) && $file != false) {
            if(unlink($path)){
                $this->returnMessage(false, 0);
                return true;
            }else{
                $this->returnMessage(true, 1);
                return false;
            }
        }else{
            $this->returnMessage(true, 2, array('path' => $path, "file" => $file));
            return false;
        }
    }

}