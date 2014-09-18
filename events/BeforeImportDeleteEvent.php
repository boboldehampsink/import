<?php
namespace Craft;

class BeforeImportDeleteEvent extends Event 
{

    // Whether to proceed after this event is raised
    public $proceed = true;

}
