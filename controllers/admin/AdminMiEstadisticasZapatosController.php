<?php
/**
 * Controller para el menú lateral
 */

class AdminMiEstadisticasZapatosController extends ModuleAdminController
{
    public function __construct()
    {
        parent::__construct();
        
        // Redirigir a la configuración del módulo
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules', true, [], [
            'configure' => 'miestadisticaszapatos',
            'tab_module' => 'analytics_stats',
            'module_name' => 'miestadisticaszapatos'
        ]));
    }
}