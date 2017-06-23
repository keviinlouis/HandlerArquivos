<?php

/**
 * Created by PhpStorm.
 * User: Kevin
 * Date: 23/06/2017
 * Time: 12:02
 */
class HandlerArquivos
{
    private $formatosFile = array();

    function setFormatosFile(Array $formatosFile){
        $this->formatosFile = $formatosFile;
    }
    function clearPath($path){

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

    function returnMessage($erro, $msg, $campos = array()){
        $array = array('erro' => $erro, 'msg' => $msg);
        if($campos != null){
            foreach($campos as $nomeCampo => $campo){
                 $array[$nomeCampo] = $campo;
            }
        }
        return $array;
    }


    function getAll($path, $separador = false){
        $arquivos = array();
        $path = $this->clearPath($path);

        //Se for um diretorio
        if(is_dir($path)){
            foreach (new DirectoryIterator($path) as $fileInfo) {
                if(is_null($this->formatosFile)){
                    if($fileInfo->isFile()){
                        $arquivos[$fileInfo->getBasename()] = $fileInfo->getPath();
                    }
                }else{
                    if (in_array($fileInfo->getExtension(), $this->formatosFile) ) {
                        if($fileInfo->isFile()){
                            $arquivos[$fileInfo->getBasename()] = $fileInfo->getPath();
                        }
                    }
                }

            }
            if(count($arquivos) <= 0 ){
                return false;
            }
            //Voltar os arquivos já separados por um separador de string
            //Formato: NomeDoArquivoSeparado => NomeDoArquivoNãoSeparado
            if($separador != false){
                $arquivosSeparados = array();
                foreach ($arquivos as $nomeArquivo => $caminho){
                    $nome = explode($separador, $nomeArquivo);
                    $arquivosSeparados[$nome[0]] = $nomeArquivo;

                }
                return $arquivosSeparados;
            }
            return $arquivos;
        }else{
            return false;
        }
    }

    function find($path, $comparador, $separador = false, $indice = 0){
        $arquivos = $this->getAll($path, '.');
        if($arquivos != false && is_array($arquivos)){
            foreach($arquivos as $nomeSemExtensao => $nomeComExtensao){
                if($separador == false){
                    if($nomeSemExtensao == $comparador){
                        return $nomeComExtensao;
                    }
                }else{
                    $nome = explode($separador, $nomeSemExtensao);
                    if($nome[$indice] == $comparador){
                        return $nomeComExtensao;
                    }
                }
            }
        }
        return false;
    }

    function remove($path, $comparador, $separador = false, $indice = 0){
        $arquivo = $this->find($path, $comparador, $separador, $indice);
        $path = $this->clearPath($path).$arquivo;

        if (is_file($path) && $arquivo != false) {
            if(unlink($path)){
                return $this->returnMessage(false, 'Requisição Feita com Sucesso');
            }else{
                return $this->returnMessage(true, 'Não foi possivel remover o arquivo');
            }
        }else{
            return $this->returnMessage(true, "Arquivo não encontrado: $path, Arquivo: $arquivo");
        }
    }

    function add($path, $nome){
        if(!is_dir($this->clearPath($path))){
            if(!mkdir($this->clearPath($path), 0755, true)){
                return $this->returnMessage(true, 'Não foi possivel criar a pasta');
            }
        }
        if(is_null($_FILES)){
           return $this->returnMessage(true, 'Não há arquivos para adicionar');
        }
        foreach ($_FILES as $arq => $array) {
            if (strlen($array['name']) > 0) {

                $extensão = explode('.', $array['name']);
                $nome = $nome.".".$extensão[1];

                if(move_uploaded_file($array['tmp_name'], $this->clearPath($path).$nome)){
                    return $this->returnMessage(false, "Requisição feita com Sucesso", array('path' => $path.$nome));
                }else{
                    return $this->returnMessage(true,  'Erro ao Mover o Arquivo');
                }

            }else{
                return $this->returnMessage(true, 'Nome muito curto');
            }
        }
        return false;
    }

    function rename($path, $newName, $oldName = null){

        $path = $this->clearPath($path);
        $arquivo = $this->find($path, $oldName);

        if($arquivo == false){
            return $this->returnMessage(true, 'Não foi possivel encontrar o arquivo');
        }

        $extension = explode('.', $arquivo);

        if(is_dir($path)){
            $arquivo = $path . $arquivo;
        }

        if (rename($arquivo, $newName.$extension[1])){
            return $this->returnMessage(false, 'Requisição feita com sucesso', array('path' => $path.$newName.$extension[1]));
        }else{
            return $this->returnMessage(true, 'Não foi possivel renomear o arquivo');

        }
    }
}