<?php
/**
 * Created by PhpStorm.
 * User: gabriel
 * Date: 31/05/18
 * Time: 19:51
 */

namespace app\classes;

use Faker\Factory;
use app\models\Connection;


class SortDataAjax
{

    /**
     * @var \Faker\Generator
     */
    private $faker;

    /**
     * @var array
     */
    private $types;

    /**
     * @var string
     */
    private $tabela;

    /**
     * Prepara as coisas antes de executar tudo
     *
     * SortDataAjax constructor.
     */
    public function __construct(){

        $this->faker = Factory::create();

        $this->types = [
            "varchar(30)" => $this->faker->name,
            "int(2)" => $this->faker->numberBetween(0,80),
            "int(11)" => $this->faker->numberBetween(0,80)
        ];
    }

    /**
     *
     * @param $colunas
     * @param $tabela
     * @return false|string
     * @throws \Exception
     */
    public function fillDataBase($colunas, $tabela)
    {
        //Trasnforma o Json em array
        $colunasArray = json_decode($colunas);
        $this->tabela = $tabela;

        //Prepara o insert
        return $this->insertPrepare($colunasArray);

    }

    /**
     * Prepara o insert
     * @param $dados
     * @return false|string
     * @throws \Exception
     */
    private function insertPrepare($dados)
    {
        //Trata os dados para um array
        $this->dataTrate($dados);

        //INSERT INTO $tabela ($campo1, ... ) VALUES ($item1, ...)
        $insert = "INSERT INTO {$this->tabela} (";

        //Gera a primeira parte do INSERT
        $cols = "";
        foreach ($dados as $key => $colunas) {
            foreach ($colunas as $coluna) {
                $cols .= " " . $coluna['nome'] . ",";
            }
        }

        //Remove a última vírgula das colunas
        $insert .= $this->trataVirgula($cols);

        $insert .= " ) VALUES ( ";

        //Gera os dados de fato usando o \Faker
        $vals = "";
        foreach ($dados as $key => $colunas) {
            foreach ($colunas as $coluna) {

                $dado = $this->gerarDado($coluna['valor']);

                if (is_null($dado)) {
                    $vals .= " NULL, ";
                } else {
                    $vals .= " '" . $dado . "',";
                }
            }
        }

        //Remove a última vírgula dos valores
        $insert .= $this->trataVirgula($vals);

        $insert .= " )";

        $stmt = Connection::insert($insert);

        if ($stmt->errorCode() == "00000") {
            return json_encode(['msg' => "Registro inserido com sucesso!", "status" => "success"]);
        } else {
            return json_encode(['msg' => "Falha ao inserir registro: " . $stmt->errorInfo()[2], 'status' => "error"]);
        }
    }

    /**
     * Deixa o array mais organizado com map
     * @param $data
     */
    private function dataTrate(&$data)
    {
        //Inicializa os arrays de colunas e valores
        $colunas = [];
        foreach ($data as $column) {
            $coluna = [
                'nome' => $column->nomeColuna,
                'valor' => $column->tipoColuna
            ];

            array_push($colunas, $coluna);
        }

        $data = [];
        array_push($data, $colunas);
    }

    /**
     * tudo pra minúsculo e depois chama outra função
     * @param $tipoDado
     * @return mixed
     */
    private function gerarDado($tipoDado)
    {
        $tipo = strtolower($tipoDado);
        return $this->getDado($tipo);
    }

    /**
     * Gera um dado de acordo com o tipo passado
     * @param $tipo
     * @return mixed
     */
    private function getDado($tipo)
    {
      return $this->types[$tipo];
    }

    /**
     * @param $string
     * @return bool|string
     */
    public function trataVirgula($string)
    {
        //Procura a posição da última ocorrência da vírgula
        $last = strripos($string, ",");
        //Onde ficarão os parâmetros
        $params = "";
        //Verifica se a última posição é de fato uma vírgula. Se sim, põe a string sem a vírgula em $params
        if ($string[$last] === ',') {
            $params = substr($string, 0, $last);
        }
        return $params;
    }
}