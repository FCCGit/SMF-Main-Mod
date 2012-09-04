<?php

function template_main()
{
        global $context, $boarddir;
        if(isset($context['director_include'])){
                $director_include = $boarddir.'/Sources/DirectorTools/'.$context['director_include'];
                include($director_include);
        }
}

?>