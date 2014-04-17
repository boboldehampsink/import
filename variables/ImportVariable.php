<?php 

namespace Craft;

class ImportVariable {

    public function history() {
    
        return craft()->import_history->show();
    
    }

}