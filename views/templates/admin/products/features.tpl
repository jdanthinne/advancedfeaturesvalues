{*
* 2007-2015 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2015 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}

{if isset($product->id)}
<div id="product-features" class="panel product-tab">
	<input type="hidden" name="submitted_tabs[]" value="Features" />
	<h3>{l s='Assign features to this product' mod='advancedfeaturesvalues'}</h3>

	<div class="alert alert-info">
		{l s='You can specify a value for each relevant feature regarding this product. Empty fields will not be displayed.' mod='advancedfeaturesvalues'}<br/>
		{l s='You can either create a specific value, or select among the existing pre-defined values you\'ve previously added.' mod='advancedfeaturesvalues'}
	</div>

	<table class="table">
		<thead>
			<tr>
				<th><span class="title_box">{l s='Feature' mod='advancedfeaturesvalues'}</span></th>
				<th><span class="title_box">{l s='Pre-defined value' mod='advancedfeaturesvalues'}</span></th>
				<th><span class="title_box"><u>{l s='or' mod='advancedfeaturesvalues'}</u> {l s='Customized value' mod='advancedfeaturesvalues'}</span></th>
			</tr>
		</thead>

		<tbody>
		{foreach from=$available_features item=available_feature}
			<tr>
				<td>{$available_feature.name|escape:'html'}</td>
				<td>
				{if sizeof($available_feature.featureValues)}
					<select multiple="true" size="6" id="feature_{$available_feature.id_feature|intval}_value" name="feature_{$available_feature.id_feature|intval}_value[]"
						onchange="$('.custom_{$available_feature.id_feature|intval}_').val('');">
						<option value="0">---</option>
						{foreach from=$available_feature.featureValues item=value}
						<option value="{$value.id_feature_value}"{if $value.id_feature_value|in_array:$available_feature.current_item}selected="selected"{/if} >
							{$value.value|truncate:40}
						</option>
						{/foreach}
					</select>
				{else}
					<input type="hidden" name="feature_{$available_feature.id_feature|intval}_value" value="0" />
					<span>{l s='N/A' mod='advancedfeaturesvalues'} -
						<a href="{$link->getAdminLink('AdminFeatures')|escape:'html':'UTF-8'}&amp;addfeature_value&amp;id_feature={$available_feature.id_feature}"
					 	class="confirm_leave btn btn-link"><i class="icon-plus-sign"></i> {l s='Add pre-defined values first' mod='advancedfeaturesvalues'} <i class="icon-external-link-sign"></i></a>
					</span>
				{/if}
				</td>
				<td>

				<div class="row lang-0" style='display: none;'>
					<div class="col-lg-9">
						<textarea class="custom_{$available_feature.id_feature|intval}_ALL textarea-autosize" name="custom_{$available_feature.id_feature|intval}_ALL"
								cols="40" style='background-color:#CCF'	rows="1" onkeyup="{foreach from=$languages key=k item=language}$('.custom_{$available_feature.id_feature|intval}_{$language.id_lang|intval}').val($(this).val());{/foreach}" >{$available_feature.val[1].value|escape:'html':'UTF-8'|default:""}</textarea>

					</div>
					{if $languages|count > 1}
						<div class="col-lg-3">
							<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
										{l s='ALL' mod='advancedfeaturesvalues'}
								<span class="caret"></span>
							</button>
							<ul class="dropdown-menu">
								{foreach from=$languages item=language}
									<li>
										<a href="javascript:void(0);" onclick="restore_lng($(this),{$language.id_lang|intval});">{$language.iso_code}</a>
									</li>
								{/foreach}
							</ul>
						</div>
					{/if}
				</div>

				{foreach from=$languages key=k item=language}
					{if $languages|count > 1}
					<div class="row translatable-field lang-{$language.id_lang|intval}">
						<div class="col-lg-9">
						{/if}
						<textarea
								class="custom_{$available_feature.id_feature}_{$language.id_lang|intval} textarea-autosize"
								name="custom_{$available_feature.id_feature}_{$language.id_lang|intval}"
								cols="40"
								rows="1"
								onkeyup="if (isArrowKey(event)) return ;$('#feature_{$available_feature.id_feature|intval}_value').val(0);" >{$available_feature.val[$k].value|escape:'html':'UTF-8'|default:""}</textarea>

					{if $languages|count > 1}
						</div>
						<div class="col-lg-3">
							<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
								{$language.iso_code|escape:'html'}
								<span class="caret"></span>
							</button>
							<ul class="dropdown-menu">
								<li><a href="javascript:void(0);" onclick="all_languages($(this));">{l s='ALL' mod='advancedfeaturesvalues'}</a></li>
								{foreach from=$languages item=language}
								<li>
									<a href="javascript:hideOtherLanguage({$language.id_lang|intval});">{$language.iso_code|escape:'html'}</a>
								</li>
								{/foreach}
							</ul>
						</div>
					</div>
					{/if}
					{/foreach}
				</td>

			</tr>
			{foreachelse}
			<tr>
				<td colspan="3" style="text-align:center;"><i class="icon-warning-sign"></i> {l s='No features have been defined' mod='advancedfeaturesvalues'}</td>
			</tr>
			{/foreach}
		</tbody>
	</table>

	<a href="{$link->getAdminLink('AdminFeatures')|escape:'html':'UTF-8'}&amp;addfeature" class="btn btn-link confirm_leave button">
		<i class="icon-plus-sign"></i> {l s='Add a new feature' mod='advancedfeaturesvalues'} <i class="icon-external-link-sign"></i>
	</a>
	<div class="panel-footer">
		<a href="{$link->getAdminLink('AdminProducts')|escape:'html':'UTF-8'}" class="btn btn-default"><i class="process-icon-cancel"></i> {l s='Cancel' mod='advancedfeaturesvalues'}</a>
		<button type="submit" name="submitAddproduct" class="btn btn-default pull-right" disabled="disabled"><i class="process-icon-loading"></i> {l s='Save' mod='advancedfeaturesvalues'}</button>
		<button type="submit" name="submitAddproductAndStay" class="btn btn-default pull-right" disabled="disabled"><i class="process-icon-loading"></i> {l s='Save and stay' mod='advancedfeaturesvalues'}</button>
	</div>
</div>
<script type="text/javascript">
	if (tabs_manager.allow_hide_other_languages)
		hideOtherLanguage({$default_form_language|escape:'html'});
{literal}
	$(".textarea-autosize").autosize();

	function all_languages(pos)
	{
{/literal}
{if isset($languages) && is_array($languages)}
	{foreach from=$languages key=k item=language}
			pos.parents('td').find('.lang-{$language.id_lang|intval}').addClass('nolang-{$language.id_lang|intval}').removeClass('lang-{$language.id_lang|intval}');
	{/foreach}
{/if}
		pos.parents('td').find('.translatable-field').hide();
		pos.parents('td').find('.lang-0').show();
{literal}
	}

	function restore_lng(pos,i)
	{
{/literal}
{if isset($languages) && is_array($languages)}
	{foreach from=$languages key=k item=language}
			pos.parents('td').find('.nolang-{$language.id_lang|intval}').addClass('lang-{$language.id_lang|intval}').removeClass('nolang-{$language.id_lang|intval}');
	{/foreach}
{/if}
{literal}
		pos.parents('td').find('.lang-0').hide();
		hideOtherLanguage(i);
	}
</script>
{/literal}

{/if}
