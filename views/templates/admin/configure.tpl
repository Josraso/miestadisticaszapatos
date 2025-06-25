<div class="panel">
    <h3>
        <i class="icon-bar-chart"></i> {l s='Estadísticas de Zapatos' mod='miestadisticaszapatos'}
    </h3>
    
    {* Menú de navegación *}
    <ul class="nav nav-tabs">
        <li {if $report_type == 'dashboard'}class="active"{/if}>
            <a href="{$current_url|escape:'html':'UTF-8'}&report_type=dashboard">{l s='Dashboard' mod='miestadisticaszapatos'}</a>
        </li>
        <li {if $report_type == 'productos_vendidos'}class="active"{/if}>
            <a href="{$current_url|escape:'html':'UTF-8'}&report_type=productos_vendidos">{l s='Productos más vendidos' mod='miestadisticaszapatos'}</a>
        </li>
        <li {if $report_type == 'combinaciones_vendidas'}class="active"{/if}>
            <a href="{$current_url|escape:'html':'UTF-8'}&report_type=combinaciones_vendidas">{l s='Combinaciones más vendidas' mod='miestadisticaszapatos'}</a>
        </li>
        <li {if $report_type == 'sin_ventas'}class="active"{/if}>
            <a href="{$current_url|escape:'html':'UTF-8'}&report_type=sin_ventas">{l s='Sin ventas' mod='miestadisticaszapatos'}</a>
        </li>
        <li {if $report_type == 'baja_rotacion'}class="active"{/if}>
            <a href="{$current_url|escape:'html':'UTF-8'}&report_type=baja_rotacion">{l s='Baja rotación' mod='miestadisticaszapatos'}</a>
        </li>
        <li {if $report_type == 'reposicion'}class="active"{/if}>
            <a href="{$current_url|escape:'html':'UTF-8'}&report_type=reposicion">{l s='Sugerencias reposición' mod='miestadisticaszapatos'}</a>
        </li>
        <li {if $report_type == 'tallas_agotadas'}class="active"{/if}>
            <a href="{$current_url|escape:'html':'UTF-8'}&report_type=tallas_agotadas">{l s='Tallas agotadas' mod='miestadisticaszapatos'}</a>
        </li>
    </ul>
    
    {* Filtros *}
    <div class="well" style="margin-top: 20px;">
        <form method="get" action="{$current_url}" class="form-inline">
            <input type="hidden" name="controller" value="AdminModules" />
            <input type="hidden" name="configure" value="{$module_name}" />
            <input type="hidden" name="tab_module" value="analytics_stats" />
            <input type="hidden" name="module_name" value="{$module_name}" />
            <input type="hidden" name="report_type" value="{$report_type}" />
            <input type="hidden" name="token" value="{Tools::getAdminTokenLite('AdminModules')}" />
            
            {if in_array($report_type, ['productos_vendidos', 'combinaciones_vendidas'])}
                <div class="form-group">
                    <label>{l s='Desde' mod='miestadisticaszapatos'}</label>
                    <input type="date" name="date_from" value="{$date_from}" class="form-control" />
                </div>
                <div class="form-group">
                    <label>{l s='Hasta' mod='miestadisticaszapatos'}</label>
                    <input type="date" name="date_to" value="{$date_to}" class="form-control" />
                </div>
            {/if}
            
            {if $report_type == 'sin_ventas'}
                <div class="form-group">
                    <label>{l s='Últimos meses' mod='miestadisticaszapatos'}</label>
                    <select name="meses" class="form-control">
                        <option value="3" {if Tools::getValue('meses') == 3}selected{/if}>3 meses</option>
                        <option value="6" {if Tools::getValue('meses', 6) == 6}selected{/if}>6 meses</option>
                        <option value="12" {if Tools::getValue('meses') == 12}selected{/if}>12 meses</option>
                    </select>
                </div>
            {/if}
            
            {if $report_type == 'baja_rotacion'}
                <div class="form-group">
                    <label>{l s='Últimos días' mod='miestadisticaszapatos'}</label>
                    <input type="number" name="dias" value="{Tools::getValue('dias', 90)}" class="form-control" />
                </div>
            {/if}
            
            {if $report_type == 'reposicion'}
                <div class="form-group">
                    <label>{l s='Días análisis' mod='miestadisticaszapatos'}</label>
                    <input type="number" name="dias_analisis" value="{Tools::getValue('dias_analisis', 90)}" class="form-control" style="width: 80px;" />
                </div>
                <div class="form-group">
                    <label>{l s='Días stock' mod='miestadisticaszapatos'}</label>
                    <input type="number" name="dias_stock" value="{Tools::getValue('dias_stock', 30)}" class="form-control" style="width: 80px;" />
                </div>
            {/if}
            
            {if $report_type == 'tallas_agotadas'}
                <div class="form-group">
                    <label>{l s='Últimos días' mod='miestadisticaszapatos'}</label>
                    <input type="number" name="dias" value="{Tools::getValue('dias', 30)}" class="form-control" style="width: 80px;" />
                </div>
                <div class="form-group">
                    <label>{l s='Mín. ventas' mod='miestadisticaszapatos'}</label>
                    <input type="number" name="min_ventas" value="{Tools::getValue('min_ventas', 5)}" class="form-control" style="width: 80px;" />
                </div>
            {/if}
            
            <button type="submit" class="btn btn-primary">
                <i class="icon-search"></i> {l s='Filtrar' mod='miestadisticaszapatos'}
            </button>
            
            {if $data}
                <a href="{$export_url}" class="btn btn-success">
                    <i class="icon-download"></i> {l s='Exportar CSV' mod='miestadisticaszapatos'}
                </a>
            {/if}
        </form>
    </div>
    
    {* Contenido principal *}
    <div class="report-content">
        {if $report_type == 'dashboard'}
            {* Dashboard con widgets *}
            <div class="row">
                <div class="col-md-3">
                    <div class="panel panel-primary">
                        <div class="panel-heading">
                            <h3 class="panel-title">{l s='Ventas últimos 30 días' mod='miestadisticaszapatos'}</h3>
                        </div>
                        <div class="panel-body text-center">
                            <h2>{if $data.ventas_ultimos_30_dias}{$data.ventas_ultimos_30_dias.total_ingresos|string_format:"%.2f"} €{else}0 €{/if}</h2>
                            <p>{if $data.ventas_ultimos_30_dias}{$data.ventas_ultimos_30_dias.num_pedidos}{else}0{/if} pedidos</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="panel panel-warning">
                        <div class="panel-heading">
                            <h3 class="panel-title">{l s='Combinaciones sin ventas' mod='miestadisticaszapatos'}</h3>
                        </div>
                        <div class="panel-body text-center">
                            <h2>{$data.combinaciones_sin_ventas}</h2>
                            <p>{l s='En los últimos 3 meses' mod='miestadisticaszapatos'}</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="panel panel-danger">
                        <div class="panel-heading">
                            <h3 class="panel-title">{l s='Valor stock muerto' mod='miestadisticaszapatos'}</h3>
                        </div>
                        <div class="panel-body text-center">
                            <h2>{$data.valor_stock_muerto|string_format:"%.2f"} €</h2>
                            <p>{l s='Sin movimiento 6 meses' mod='miestadisticaszapatos'}</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="panel panel-info">
                        <div class="panel-heading">
                            <h3 class="panel-title">{l s='Alertas reposición' mod='miestadisticaszapatos'}</h3>
                        </div>
                        <div class="panel-body text-center">
                            <h2>{$data.alertas_reposicion}</h2>
                            <p>{l s='Productos a reponer' mod='miestadisticaszapatos'}</p>
                        </div>
                    </div>
                </div>
            </div>
            
            {* Top 10 productos más vendidos *}
            {if $data.productos_mas_vendidos}
            <div class="panel">
                <h3>{l s='Top 10 Productos más vendidos (últimos 30 días)' mod='miestadisticaszapatos'}</h3>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>{l s='ID' mod='miestadisticaszapatos'}</th>
                            <th>{l s='Producto' mod='miestadisticaszapatos'}</th>
                            <th>{l s='Referencia' mod='miestadisticaszapatos'}</th>
                            <th>{l s='Unidades vendidas' mod='miestadisticaszapatos'}</th>
                            <th>{l s='Ingresos' mod='miestadisticaszapatos'}</th>
                            <th>{l s='Acciones' mod='miestadisticaszapatos'}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach from=$data.productos_mas_vendidos item=producto}
                            <tr>
                                <td>{$producto.id_product}</td>
                                <td>{$producto.name}</td>
                                <td>{$producto.reference}</td>
                                <td>{$producto.total_vendido}</td>
                                <td>{$producto.total_ingresos|string_format:"%.2f"} €</td>
                                <td>
                                    <a href="#" class="btn btn-default btn-xs view-product-stats" data-id="{$producto.id_product}">
                                        <i class="icon-bar-chart"></i> {l s='Ver estadísticas' mod='miestadisticaszapatos'}
                                    </a>
                                </td>
                            </tr>
                        {/foreach}
                    </tbody>
                </table>
            </div>
            {/if}
            
        {elseif $data}
            {* Tabla genérica para otros reportes *}
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        {foreach from=$data[0] key=column item=value}
                            <th>{$column|replace:'_':' '|capitalize}</th>
                        {/foreach}
                    </tr>
                </thead>
                <tbody>
                    {foreach from=$data item=row}
                        <tr>
                            {foreach from=$row item=value}
                                <td>
                                    {if is_numeric($value) && strpos($value, '.') !== false}
                                        {$value|string_format:"%.2f"}
                                    {else}
                                        {$value}
                                    {/if}
                                </td>
                            {/foreach}
                        </tr>
                    {/foreach}
                </tbody>
            </table>
        {else}
            <div class="alert alert-info">
                {l s='No hay datos para mostrar con los filtros seleccionados.' mod='miestadisticaszapatos'}
            </div>
        {/if}
    </div>
</div>

{* Modal para estadísticas de producto *}
<div class="modal fade" id="productStatsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">{l s='Estadísticas del producto' mod='miestadisticaszapatos'}</h4>
            </div>
            <div class="modal-body">
                <div id="productStatsContent">
                    <p class="text-center"><i class="icon-spinner icon-spin"></i> {l s='Cargando...' mod='miestadisticaszapatos'}</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
// URL para AJAX
var ajax_url = '{$ajax_url|escape:"javascript":"UTF-8"}';

// Manejar clicks en ver estadísticas
$(document).on('click', '.view-product-stats', function(e) {
    e.preventDefault();
    var productId = $(this).data('id');
    
    $('#productStatsModal').modal('show');
    $('#productStatsContent').html('<p class="text-center"><i class="icon-spinner icon-spin"></i> Cargando...</p>');
    
    $.ajax({
        url: ajax_url,
        type: 'POST',
        data: {
            action: 'GetProductStats',
            id_product: productId
        },
        success: function(response) {
            var data = JSON.parse(response);
            var html = '<div class="row">';
            
            // Tabla de tallas
            if (data.tallas && data.tallas.length > 0) {
                html += '<div class="col-md-12">';
                html += '<h4>Análisis de tallas</h4>';
                html += '<table class="table table-condensed">';
                html += '<thead><tr><th>Talla</th><th>Vendidas</th><th>Stock</th><th>% Ventas</th></tr></thead>';
                html += '<tbody>';
                
                $.each(data.tallas, function(i, talla) {
                    var stockClass = talla.stock_actual == 0 ? 'danger' : (talla.stock_actual < 5 ? 'warning' : '');
                    html += '<tr class="' + stockClass + '">';
                    html += '<td>' + talla.talla + '</td>';
                    html += '<td>' + talla.unidades_vendidas + '</td>';
                    html += '<td>' + talla.stock_actual + '</td>';
                    html += '<td>' + talla.porcentaje_ventas + '%</td>';
                    html += '</tr>';
                });
                
                html += '</tbody></table>';
                html += '</div>';
            }
            
            html += '</div>';
            $('#productStatsContent').html(html);
        },
        error: function() {
            $('#productStatsContent').html('<div class="alert alert-danger">Error al cargar las estadísticas</div>');
        }
    });
});
</script>