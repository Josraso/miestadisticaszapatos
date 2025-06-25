<?php
/**
 * VERSIÓN CON CONFIGURACIÓN - ACCESO DIRECTO
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class MiEstadisticasZapatos extends Module
{
    public function __construct()
    {
        $this->name = 'miestadisticaszapatos';
        $this->tab = 'analytics_stats';
        $this->version = '1.0.0';
        $this->author = 'Tu Nombre';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Mi Estadísticas Zapatos');
        $this->description = $this->l('Módulo de estadísticas avanzadas para tiendas de calzado');
        $this->confirmUninstall = $this->l('¿Estás seguro de querer desinstalar este módulo?');
    }

    public function install()
    {
        return parent::install() &&
            $this->installTab();
    }
    
    /**
     * Hook para cargar CSS y JS en el backoffice
     */
    public function hookDisplayBackOfficeHeader()
    {
        if (Tools::getValue('configure') == $this->name) {
            $this->context->controller->addCSS($this->_path.'views/css/admin.css');
            $this->context->controller->addJS($this->_path.'views/js/admin.js');
        }
    }

    public function uninstall()
    {
        return parent::uninstall() && 
            $this->uninstallTab();
    }

    /**
     * INSTALACIÓN DEL TAB EN EL MENÚ PRINCIPAL
     */
    private function installTab()
    {
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminMiEstadisticasZapatos';
        $tab->name = array();
        
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = 'Estadísticas Zapatos';
        }
        
        // Ponerlo en el menú principal, NO bajo estadísticas
        $tab->id_parent = 0; // 0 = Menú principal
        $tab->module = $this->name;
        $tab->position = 10;
        
        return $tab->add();
    }

    private function uninstallTab()
    {
        $id_tab = (int)Tab::getIdFromClassName('AdminMiEstadisticasZapatos');
        if ($id_tab) {
            $tab = new Tab($id_tab);
            return $tab->delete();
        }
        return true;
    }

    /**
     * PÁGINA DE CONFIGURACIÓN DEL MÓDULO
     * AQUÍ ES DONDE ACCEDES A TODO
     */
    public function getContent()
    {
        $output = '';
        
        // Procesar formulario si se envió
        if (Tools::isSubmit('submit'.$this->name)) {
            // Aquí puedes guardar configuraciones si las necesitas
            $output .= $this->displayConfirmation($this->l('Configuración guardada'));
        }
        
        // Obtener tipo de reporte
        $report_type = Tools::getValue('report_type', 'dashboard');
        $export = Tools::getValue('export', false);
        
        // Fechas para filtros
        $date_from = Tools::getValue('date_from', date('Y-m-d', strtotime('-30 days')));
        $date_to = Tools::getValue('date_to', date('Y-m-d'));
        
        // Procesar respuestas AJAX
        if (Tools::getValue('ajax')) {
            $this->processAjax();
            exit;
        }
        
        $data = array();
        
        // Obtener datos según el tipo de reporte
        switch ($report_type) {
            case 'dashboard':
                $data = $this->getDashboardData();
                break;
                
            case 'productos_vendidos':
                $data = $this->getProductosMasVendidos($date_from, $date_to, 50);
                break;
                
            case 'combinaciones_vendidas':
                $data = $this->getCombinacionesMasVendidas($date_from, $date_to, 100);
                break;
                
            case 'sin_ventas':
                $meses = Tools::getValue('meses', 6);
                $data = $this->getCombinacionesSinVentas($meses);
                break;
                
            case 'baja_rotacion':
                $dias = Tools::getValue('dias', 90);
                $data = $this->getProductosBajaRotacion($dias);
                break;
                
            case 'reposicion':
                $dias_analisis = Tools::getValue('dias_analisis', 90);
                $dias_stock = Tools::getValue('dias_stock', 30);
                $data = $this->getSugerenciasReposicion($dias_analisis, $dias_stock);
                break;
                
            case 'tallas_agotadas':
                $dias = Tools::getValue('dias', 30);
                $min_ventas = Tools::getValue('min_ventas', 5);
                $data = $this->getTallasAgotadasConDemanda($dias, $min_ventas);
                break;
        }
        
        // Si se solicita exportación
        if ($export && !empty($data)) {
            $this->exportToExcel($data, 'reporte_'.$report_type);
            exit;
        }
        
        // Obtener grupos de atributos
        $attribute_groups = AttributeGroup::getAttributesGroups($this->context->language->id);
        
        // URL actual para los enlaces
        $current_url = $_SERVER['REQUEST_URI'];
        $base_url = $this->context->link->getAdminLink('AdminModules', true).'&configure='.$this->name;
        
        // Asignar variables a Smarty
        $this->context->smarty->assign(array(
            'module_name' => $this->name,
            'report_type' => $report_type,
            'data' => $data,
            'date_from' => $date_from,
            'date_to' => $date_to,
            'attribute_groups' => $attribute_groups,
            'current_url' => $base_url,
            'export_url' => $base_url.'&export=1&report_type='.$report_type,
            'ajax_url' => $base_url.'&ajax=1'
        ));
        
        // Añadir CSS y JS
        $this->context->controller->addCSS($this->_path.'views/css/admin.css');
        $this->context->controller->addJS($this->_path.'views/js/admin.js');
        $this->context->controller->addJS('https://cdn.jsdelivr.net/npm/chart.js');
        
        return $output.$this->display(__FILE__, 'views/templates/admin/configure.tpl');
    }

    /**
     * Procesar peticiones AJAX
     */
    private function processAjax()
    {
        $action = Tools::getValue('action');
        
        switch ($action) {
            case 'GetProductStats':
                $id_product = (int)Tools::getValue('id_product');
                $stats = array(
                    'tallas' => $this->getAnalisisTallasPorModelo($id_product),
                    'estacionalidad' => $this->getAnalisisEstacionalidad($id_product)
                );
                die(json_encode($stats));
                break;
                
            case 'GetQuickStats':
                $date_from = date('Y-m-d', strtotime('-7 days'));
                $date_to = date('Y-m-d');
                
                $sql_ventas = 'SELECT SUM(total_paid_tax_incl) as total 
                              FROM '._DB_PREFIX_.'orders 
                              WHERE valid = 1 
                              AND date_add BETWEEN "'.pSQL($date_from).'" AND "'.pSQL($date_to).'"';
                $ventas = Db::getInstance()->getValue($sql_ventas);
                
                $sql_unidades = 'SELECT SUM(od.product_quantity) as total 
                                FROM '._DB_PREFIX_.'order_detail od
                                LEFT JOIN '._DB_PREFIX_.'orders o ON od.id_order = o.id_order
                                WHERE o.valid = 1 
                                AND o.date_add BETWEEN "'.pSQL($date_from).'" AND "'.pSQL($date_to).'"';
                $unidades = Db::getInstance()->getValue($sql_unidades);
                
                $productos_top = $this->getProductosMasVendidos($date_from, $date_to, 1);
                $producto_estrella = !empty($productos_top) ? $productos_top[0]['name'] : 'N/A';
                
                $alertas_stock = count($this->getTallasAgotadasConDemanda(7, 3));
                $alertas_rotacion = count($this->getProductosBajaRotacion(30, 5));
                
                $stats = array(
                    'ventas' => number_format($ventas ?: 0, 2, ',', '.'),
                    'unidades' => number_format($unidades ?: 0, 0, ',', '.'),
                    'producto_estrella' => Tools::substr($producto_estrella, 0, 30),
                    'alertas' => $alertas_stock + $alertas_rotacion
                );
                
                die(json_encode($stats));
                break;
        }
    }

    /**
     * TODOS LOS MÉTODOS DE CONSULTAS
     */
    
    public function getProductosMasVendidos($date_from = null, $date_to = null, $limit = 20)
    {
        $sql = 'SELECT 
                p.id_product,
                pl.name,
                p.reference,
                SUM(od.product_quantity) as total_vendido,
                COUNT(DISTINCT o.id_order) as num_pedidos,
                SUM(od.total_price_tax_incl) as total_ingresos
            FROM '._DB_PREFIX_.'order_detail od
            LEFT JOIN '._DB_PREFIX_.'orders o ON od.id_order = o.id_order
            LEFT JOIN '._DB_PREFIX_.'product p ON od.product_id = p.id_product
            LEFT JOIN '._DB_PREFIX_.'product_lang pl ON p.id_product = pl.id_product AND pl.id_lang = '.(int)$this->context->language->id.'
            WHERE o.valid = 1';
            
        if ($date_from) {
            $sql .= ' AND o.date_add >= "'.pSQL($date_from).'"';
        }
        if ($date_to) {
            $sql .= ' AND o.date_add <= "'.pSQL($date_to).'"';
        }
        
        $sql .= ' GROUP BY p.id_product
            ORDER BY total_vendido DESC
            LIMIT '.(int)$limit;
            
        return Db::getInstance()->executeS($sql);
    }
    
    public function getCombinacionesMasVendidas($date_from = null, $date_to = null, $limit = 50)
    {
        $sql = 'SELECT 
                p.id_product,
                pl.name as producto,
                p.reference,
                pa.id_product_attribute,
                GROUP_CONCAT(DISTINCT al.name ORDER BY al.name SEPARATOR " - ") as combinacion,
                SUM(od.product_quantity) as total_vendido,
                COUNT(DISTINCT o.id_order) as num_pedidos,
                SUM(od.total_price_tax_incl) as total_ingresos
            FROM '._DB_PREFIX_.'order_detail od
            LEFT JOIN '._DB_PREFIX_.'orders o ON od.id_order = o.id_order
            LEFT JOIN '._DB_PREFIX_.'product p ON od.product_id = p.id_product
            LEFT JOIN '._DB_PREFIX_.'product_lang pl ON p.id_product = pl.id_product AND pl.id_lang = '.(int)$this->context->language->id.'
            LEFT JOIN '._DB_PREFIX_.'product_attribute pa ON od.product_attribute_id = pa.id_product_attribute
            LEFT JOIN '._DB_PREFIX_.'product_attribute_combination pac ON pa.id_product_attribute = pac.id_product_attribute
            LEFT JOIN '._DB_PREFIX_.'attribute_lang al ON pac.id_attribute = al.id_attribute AND al.id_lang = '.(int)$this->context->language->id.'
            WHERE o.valid = 1 AND pa.id_product_attribute IS NOT NULL';
            
        if ($date_from) {
            $sql .= ' AND o.date_add >= "'.pSQL($date_from).'"';
        }
        if ($date_to) {
            $sql .= ' AND o.date_add <= "'.pSQL($date_to).'"';
        }
        
        $sql .= ' GROUP BY pa.id_product_attribute
            ORDER BY total_vendido DESC
            LIMIT '.(int)$limit;
            
        return Db::getInstance()->executeS($sql);
    }
    
    public function getCombinacionesSinVentas($meses = 6)
    {
        $date_limit = date('Y-m-d', strtotime('-'.$meses.' months'));
        
        $sql = 'SELECT 
                p.id_product,
                pl.name as producto,
                p.reference,
                pa.id_product_attribute,
                GROUP_CONCAT(DISTINCT al.name ORDER BY al.name SEPARATOR " - ") as combinacion,
                sa.quantity as stock_actual,
                (sa.quantity * pa.wholesale_price) as valor_inmovilizado
            FROM '._DB_PREFIX_.'product_attribute pa
            LEFT JOIN '._DB_PREFIX_.'product p ON pa.id_product = p.id_product
            LEFT JOIN '._DB_PREFIX_.'product_lang pl ON p.id_product = pl.id_product AND pl.id_lang = '.(int)$this->context->language->id.'
            LEFT JOIN '._DB_PREFIX_.'stock_available sa ON pa.id_product = p.id_product AND pa.id_product_attribute = sa.id_product_attribute
            LEFT JOIN '._DB_PREFIX_.'product_attribute_combination pac ON pa.id_product_attribute = pac.id_product_attribute
            LEFT JOIN '._DB_PREFIX_.'attribute_lang al ON pac.id_attribute = al.id_attribute AND al.id_lang = '.(int)$this->context->language->id.'
            WHERE pa.id_product_attribute NOT IN (
                SELECT DISTINCT od.product_attribute_id 
                FROM '._DB_PREFIX_.'order_detail od
                LEFT JOIN '._DB_PREFIX_.'orders o ON od.id_order = o.id_order
                WHERE o.valid = 1 AND o.date_add >= "'.pSQL($date_limit).'"
                AND od.product_attribute_id IS NOT NULL
            )
            AND sa.quantity > 0
            GROUP BY pa.id_product_attribute
            ORDER BY valor_inmovilizado DESC';
            
        return Db::getInstance()->executeS($sql);
    }
    
    public function getProductosBajaRotacion($dias = 90, $min_stock = 1)
    {
        $date_limit = date('Y-m-d', strtotime('-'.$dias.' days'));
        
        $sql = 'SELECT 
                p.id_product,
                pl.name as producto,
                p.reference,
                pa.id_product_attribute,
                GROUP_CONCAT(DISTINCT al.name ORDER BY al.name SEPARATOR " - ") as combinacion,
                sa.quantity as stock_actual,
                COALESCE(MAX(o.date_add), "Nunca") as ultima_venta,
                COALESCE(SUM(od.product_quantity), 0) as unidades_vendidas_periodo,
                (sa.quantity * pa.wholesale_price) as valor_inmovilizado
            FROM '._DB_PREFIX_.'product_attribute pa
            LEFT JOIN '._DB_PREFIX_.'product p ON pa.id_product = p.id_product
            LEFT JOIN '._DB_PREFIX_.'product_lang pl ON p.id_product = pl.id_product AND pl.id_lang = '.(int)$this->context->language->id.'
            LEFT JOIN '._DB_PREFIX_.'stock_available sa ON pa.id_product = p.id_product AND pa.id_product_attribute = sa.id_product_attribute
            LEFT JOIN '._DB_PREFIX_.'product_attribute_combination pac ON pa.id_product_attribute = pac.id_product_attribute
            LEFT JOIN '._DB_PREFIX_.'attribute_lang al ON pac.id_attribute = al.id_attribute AND al.id_lang = '.(int)$this->context->language->id.'
            LEFT JOIN '._DB_PREFIX_.'order_detail od ON pa.id_product_attribute = od.product_attribute_id
            LEFT JOIN '._DB_PREFIX_.'orders o ON od.id_order = o.id_order AND o.valid = 1 AND o.date_add >= "'.pSQL($date_limit).'"
            WHERE sa.quantity >= '.(int)$min_stock.'
            GROUP BY pa.id_product_attribute
            HAVING unidades_vendidas_periodo < 3
            ORDER BY valor_inmovilizado DESC';
            
        return Db::getInstance()->executeS($sql);
    }
    
    public function getSugerenciasReposicion($dias_analisis = 90, $dias_stock = 30)
    {
        $date_from = date('Y-m-d', strtotime('-'.$dias_analisis.' days'));
        
        $sql = 'SELECT 
                p.id_product,
                pl.name as producto,
                p.reference,
                pa.id_product_attribute,
                GROUP_CONCAT(DISTINCT al.name ORDER BY al.name SEPARATOR " - ") as combinacion,
                sa.quantity as stock_actual,
                COALESCE(SUM(od.product_quantity), 0) as vendido_periodo,
                ROUND(COALESCE(SUM(od.product_quantity), 0) / '.$dias_analisis.' * '.$dias_stock.', 0) as stock_sugerido,
                ROUND(COALESCE(SUM(od.product_quantity), 0) / '.$dias_analisis.' * '.$dias_stock.' - sa.quantity, 0) as unidades_reponer
            FROM '._DB_PREFIX_.'product_attribute pa
            LEFT JOIN '._DB_PREFIX_.'product p ON pa.id_product = p.id_product
            LEFT JOIN '._DB_PREFIX_.'product_lang pl ON p.id_product = pl.id_product AND pl.id_lang = '.(int)$this->context->language->id.'
            LEFT JOIN '._DB_PREFIX_.'stock_available sa ON pa.id_product_attribute = sa.id_product_attribute
            LEFT JOIN '._DB_PREFIX_.'product_attribute_combination pac ON pa.id_product_attribute = pac.id_product_attribute
            LEFT JOIN '._DB_PREFIX_.'attribute_lang al ON pac.id_attribute = al.id_attribute AND al.id_lang = '.(int)$this->context->language->id.'
            LEFT JOIN '._DB_PREFIX_.'order_detail od ON pa.id_product_attribute = od.product_attribute_id
            LEFT JOIN '._DB_PREFIX_.'orders o ON od.id_order = o.id_order AND o.valid = 1 AND o.date_add >= "'.pSQL($date_from).'"
            GROUP BY pa.id_product_attribute
            HAVING vendido_periodo > 0 AND unidades_reponer > 0
            ORDER BY unidades_reponer DESC';
            
        return Db::getInstance()->executeS($sql);
    }
    
    public function getTallasAgotadasConDemanda($dias = 30, $min_ventas = 5)
    {
        $date_from = date('Y-m-d', strtotime('-'.$dias.' days'));
        
        $sql = 'SELECT 
                p.id_product,
                pl.name as producto,
                p.reference,
                pa.id_product_attribute,
                GROUP_CONCAT(DISTINCT al.name ORDER BY al.name SEPARATOR " - ") as combinacion,
                sa.quantity as stock_actual,
                COALESCE(SUM(od.product_quantity), 0) as vendido_periodo,
                COUNT(DISTINCT o.id_order) as num_pedidos_periodo
            FROM '._DB_PREFIX_.'product_attribute pa
            LEFT JOIN '._DB_PREFIX_.'product p ON pa.id_product = p.id_product
            LEFT JOIN '._DB_PREFIX_.'product_lang pl ON p.id_product = pl.id_product AND pl.id_lang = '.(int)$this->context->language->id.'
            LEFT JOIN '._DB_PREFIX_.'stock_available sa ON pa.id_product_attribute = sa.id_product_attribute
            LEFT JOIN '._DB_PREFIX_.'product_attribute_combination pac ON pa.id_product_attribute = pac.id_product_attribute
            LEFT JOIN '._DB_PREFIX_.'attribute_lang al ON pac.id_attribute = al.id_attribute AND al.id_lang = '.(int)$this->context->language->id.'
            LEFT JOIN '._DB_PREFIX_.'order_detail od ON pa.id_product_attribute = od.product_attribute_id
            LEFT JOIN '._DB_PREFIX_.'orders o ON od.id_order = o.id_order AND o.valid = 1 AND o.date_add >= "'.pSQL($date_from).'"
            WHERE sa.quantity = 0
            GROUP BY pa.id_product_attribute
            HAVING vendido_periodo >= '.(int)$min_ventas.'
            ORDER BY vendido_periodo DESC';
            
        return Db::getInstance()->executeS($sql);
    }
    
    public function getAnalisisTallasPorModelo($id_product)
    {
        $sql = 'SELECT 
                a.id_attribute,
                al.name as talla,
                COALESCE(SUM(od.product_quantity), 0) as unidades_vendidas,
                COUNT(DISTINCT o.id_order) as num_pedidos,
                sa.quantity as stock_actual,
                ROUND(COALESCE(SUM(od.product_quantity), 0) * 100.0 / (
                    SELECT SUM(od2.product_quantity)
                    FROM '._DB_PREFIX_.'order_detail od2
                    LEFT JOIN '._DB_PREFIX_.'orders o2 ON od2.id_order = o2.id_order
                    WHERE o2.valid = 1 AND od2.product_id = '.(int)$id_product.'
                ), 2) as porcentaje_ventas
            FROM '._DB_PREFIX_.'product_attribute pa
            LEFT JOIN '._DB_PREFIX_.'product_attribute_combination pac ON pa.id_product_attribute = pac.id_product_attribute
            LEFT JOIN '._DB_PREFIX_.'attribute a ON pac.id_attribute = a.id_attribute
            LEFT JOIN '._DB_PREFIX_.'attribute_lang al ON a.id_attribute = al.id_attribute AND al.id_lang = '.(int)$this->context->language->id.'
            LEFT JOIN '._DB_PREFIX_.'attribute_group ag ON a.id_attribute_group = ag.id_attribute_group
            LEFT JOIN '._DB_PREFIX_.'order_detail od ON pa.id_product_attribute = od.product_attribute_id
            LEFT JOIN '._DB_PREFIX_.'orders o ON od.id_order = o.id_order AND o.valid = 1
            LEFT JOIN '._DB_PREFIX_.'stock_available sa ON pa.id_product_attribute = sa.id_product_attribute
            WHERE pa.id_product = '.(int)$id_product.'
            AND ag.id_attribute_group IN (SELECT id_attribute_group FROM '._DB_PREFIX_.'attribute_group_lang WHERE name LIKE "%talla%" AND id_lang = '.(int)$this->context->language->id.')
            GROUP BY a.id_attribute
            ORDER BY unidades_vendidas DESC';
            
        return Db::getInstance()->executeS($sql);
    }
    
    public function getAnalisisEstacionalidad($id_product = null)
    {
        $sql = 'SELECT 
                MONTH(o.date_add) as mes,
                MONTHNAME(o.date_add) as nombre_mes,
                YEAR(o.date_add) as año,
                SUM(od.product_quantity) as unidades_vendidas,
                COUNT(DISTINCT o.id_order) as num_pedidos,
                SUM(od.total_price_tax_incl) as total_ingresos
            FROM '._DB_PREFIX_.'order_detail od
            LEFT JOIN '._DB_PREFIX_.'orders o ON od.id_order = o.id_order
            WHERE o.valid = 1';
            
        if ($id_product) {
            $sql .= ' AND od.product_id = '.(int)$id_product;
        }
        
        $sql .= ' GROUP BY YEAR(o.date_add), MONTH(o.date_add)
            ORDER BY año DESC, mes DESC
            LIMIT 24';
            
        return Db::getInstance()->executeS($sql);
    }
    
    public function getVentasPorPeriodo($periodo = 'day', $limit = 30)
    {
        $format = '%Y-%m-%d';
        if ($periodo == 'month') {
            $format = '%Y-%m';
        } elseif ($periodo == 'year') {
            $format = '%Y';
        } elseif ($periodo == 'week') {
            $format = '%Y-%u';
        }
        
        $sql = 'SELECT 
                DATE_FORMAT(o.date_add, "'.$format.'") as periodo,
                COUNT(o.id_order) as num_pedidos,
                SUM(o.total_paid_tax_incl) as total_ingresos,
                SUM(od.product_quantity) as unidades_vendidas,
                COUNT(DISTINCT o.id_customer) as clientes_unicos
            FROM '._DB_PREFIX_.'orders o
            LEFT JOIN '._DB_PREFIX_.'order_detail od ON o.id_order = od.id_order
            WHERE o.valid = 1
            GROUP BY periodo
            ORDER BY periodo DESC
            LIMIT '.(int)$limit;
            
        return Db::getInstance()->executeS($sql);
    }
    
    public function getMejorDiaSemana()
    {
        $sql = 'SELECT 
                DAYNAME(o.date_add) as dia_semana,
                DAYOFWEEK(o.date_add) as num_dia,
                COUNT(o.id_order) as num_pedidos,
                SUM(o.total_paid_tax_incl) as total_ingresos,
                AVG(o.total_paid_tax_incl) as ticket_medio
            FROM '._DB_PREFIX_.'orders o
            WHERE o.valid = 1
            GROUP BY DAYOFWEEK(o.date_add)
            ORDER BY num_dia';
            
        return Db::getInstance()->executeS($sql);
    }
    
    public function getEstadisticasMetodosPago($date_from = null, $date_to = null)
    {
        $sql = 'SELECT 
                o.payment as metodo_pago,
                COUNT(o.id_order) as num_pedidos,
                SUM(o.total_paid_tax_incl) as total_ingresos,
                AVG(o.total_paid_tax_incl) as ticket_medio,
                SUM(CASE WHEN o.current_state IN (6, 7) THEN 1 ELSE 0 END) as cancelados,
                ROUND(SUM(CASE WHEN o.current_state IN (6, 7) THEN 1 ELSE 0 END) * 100.0 / COUNT(o.id_order), 2) as tasa_cancelacion
            FROM '._DB_PREFIX_.'orders o
            WHERE 1=1';
            
        if ($date_from) {
            $sql .= ' AND o.date_add >= "'.pSQL($date_from).'"';
        }
        if ($date_to) {
            $sql .= ' AND o.date_add <= "'.pSQL($date_to).'"';
        }
        
        $sql .= ' GROUP BY o.payment
            ORDER BY total_ingresos DESC';
            
        return Db::getInstance()->executeS($sql);
    }
    
    public function getValorInmovilizadoPorAtributo($id_attribute_group)
    {
        $sql = 'SELECT 
                a.id_attribute,
                al.name as atributo,
                COUNT(DISTINCT pa.id_product_attribute) as num_combinaciones,
                SUM(sa.quantity) as stock_total,
                SUM(sa.quantity * pa.wholesale_price) as valor_inmovilizado
            FROM '._DB_PREFIX_.'attribute a
            LEFT JOIN '._DB_PREFIX_.'attribute_lang al ON a.id_attribute = al.id_attribute AND al.id_lang = '.(int)$this->context->language->id.'
            LEFT JOIN '._DB_PREFIX_.'product_attribute_combination pac ON a.id_attribute = pac.id_attribute
            LEFT JOIN '._DB_PREFIX_.'product_attribute pa ON pac.id_product_attribute = pa.id_product_attribute
            LEFT JOIN '._DB_PREFIX_.'stock_available sa ON pa.id_product_attribute = sa.id_product_attribute
            WHERE a.id_attribute_group = '.(int)$id_attribute_group.'
            GROUP BY a.id_attribute
            ORDER BY valor_inmovilizado DESC';
            
        return Db::getInstance()->executeS($sql);
    }
    
    public function getDashboardData()
    {
        return array(
            'productos_mas_vendidos' => $this->getProductosMasVendidos(date('Y-m-d', strtotime('-30 days')), null, 10),
            'combinaciones_sin_ventas' => count($this->getCombinacionesSinVentas(3)),
            'valor_stock_muerto' => $this->getValorStockMuerto(),
            'alertas_reposicion' => count($this->getSugerenciasReposicion(30, 15)),
            'ventas_ultimos_30_dias' => $this->getVentasResumen(30),
            'tallas_agotadas_demanda' => count($this->getTallasAgotadasConDemanda(30, 3))
        );
    }
    
    private function getValorStockMuerto()
    {
        $sql = 'SELECT SUM(sa.quantity * p.wholesale_price) as total
            FROM '._DB_PREFIX_.'stock_available sa
            LEFT JOIN '._DB_PREFIX_.'product p ON sa.id_product = p.id_product
            WHERE sa.id_product_attribute = 0 
            AND sa.id_product NOT IN (
                SELECT DISTINCT product_id 
                FROM '._DB_PREFIX_.'order_detail od
                LEFT JOIN '._DB_PREFIX_.'orders o ON od.id_order = o.id_order
                WHERE o.valid = 1 AND o.date_add >= "'.date('Y-m-d', strtotime('-6 months')).'"
            )';
            
        $result = Db::getInstance()->getRow($sql);
        return $result ? $result['total'] : 0;
    }
    
    private function getVentasResumen($dias)
    {
        $date_from = date('Y-m-d', strtotime('-'.$dias.' days'));
        
        $sql = 'SELECT 
                COUNT(id_order) as num_pedidos,
                SUM(total_paid_tax_incl) as total_ingresos
            FROM '._DB_PREFIX_.'orders
            WHERE valid = 1 AND date_add >= "'.pSQL($date_from).'"';
            
        return Db::getInstance()->getRow($sql);
    }
    
    public function exportToExcel($data, $filename = 'estadisticas_zapatos')
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment;filename="'.$filename.'_'.date('Y-m-d').'.csv"');
        header('Cache-Control: max-age=0');
        
        $output = fopen('php://output', 'w');
        
        // UTF-8 BOM para Excel
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        if (!empty($data)) {
            // Headers
            fputcsv($output, array_keys($data[0]), ';');
            
            // Data
            foreach ($data as $row) {
                fputcsv($output, $row, ';');
            }
        }
        
        fclose($output);
        exit;
    }
}