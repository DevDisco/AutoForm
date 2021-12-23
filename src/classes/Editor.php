<?php

use function PHPSTORM_META\type;

class Editor
{

    private Config $config;

    public function __construct(private Database $database)
    {

        $this->config = $database->config;
    }

    public function showAll(): bool|string
    {

        $tableData = $this->database->selectEditRows();
        $t = $this->config->getCurrentTable();

        if (!$tableData) {

            return false;
        }

        //todo: create a template for this
        $return = "<table class='table table-light table-striped'>\n<thead>\n";

        $return .= $this->createShowAllHeader($tableData);

        $return .= "</thead>\n<tbody>\n";

        foreach ($tableData as $row) {

            $return .= $this->createShowAllRow($row, $t);
        }

        $return .= "</tbody>\n</table>\n";

        return $return;
    }

    private function createShowAllHeader(array $tableData): string
    {
        //I just need the first row to get the keys for the table headers
        $key = array_key_first($tableData);
        $row = $tableData[$key];
        $return = "\t<tr><th>&nbsp;</th>";
        foreach ($row as $field => $value) {

            $return .= "<th scope='col'>" . $field . "</th>";
        }
        $return .= "<th>&nbsp;</th></tr>\n";

        return $return;
    }

    private function createShowAllRow(array $row, string $t): string
    {

        $return = "\t<tr>" . $this->createShowAllCheckbox($row, $t);
        foreach ($row as $field => $value) {

            $return .= "<td title=\"$field\">" . $value . "</td>";
        }
        $return .= $this->createShowAllButtons($row['id'], $t) . "</tr>\n";

        return $return;
    }

    private function createShowAllCheckbox(array $row, string $t): string
    {
        $encoded = Core::delEncode($row);
        $id = $row['id'];
        //$return = "<td><input type='checkbox' value='1' name='checked[$id]' ></td>";
        $return = "<td><button class='btn btn-danger' onClick='confirmDelete(`$t`,$id,`$encoded`)'>X</button></td>";

        return $return;
    }

    private function createShowAllButtons(string $id, string $t): string
    {

        $return = "<td><a class='btn btn-primary' href='index.php?t=$t&id=$id'>Aanpassen</a></td>";

        return $return;
    }

    public function setPrefill(): bool
    {

        $t = $this->config->getCurrentTable();
        $id = $this->config->getCurrentId();

        if ($id === false) {

            return false;
        }

        $record = $this->database->selectRecord($t, $id);

        if ($record === false) {

            return false;
        }

        Session::setPrefill($record[0]);
        return true;
    }
}
