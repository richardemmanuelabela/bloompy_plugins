<?php
defined( 'ABSPATH' ) or die();

use BookneticApp\Providers\Helpers\Helper;
use BookneticAddon\BloompyTenants\BloompyTenantsAddon;
use function BookneticAddon\BloompyTenants\bkntc__;

$companyName = ( empty($parameters['info']['tenant_company_name']) ) ? $parameters['info']['company_name'] : $parameters['info']['tenant_company_name'];
?>
<script src="<?php echo BloompyTenantsAddon::loadAsset('assets/backend/js/edit.js')?>" id="tenant-info-script" data-id="<?php echo (int)$parameters['id']?>"></script>
<link rel="stylesheet" href="<?php echo BloompyTenantsAddon::loadAsset('assets/backend/css/edit.css')?>" type="text/css">
<script src="<?php echo Helper::assets('plugins/summernote/summernote-lite.min.js')?>"></script>
<link rel="stylesheet" href="<?php echo Helper::assets('plugins/summernote/summernote-lite.min.css')?>">
<script src="<?php echo Helper::assets('js/summernote.js')?>"></script>
<link rel="stylesheet" href="<?php echo Helper::assets('css/summernote.css')?>" type="text/css">


<div class="m_header clearfix">
	<div class="m_head_title float-left"><?php echo bkntc__('Booking Page Info')?></div>
	<div class="m_head_actions float-right">
		<button type="button" class="btn btn-lg btn-success float-right ml-1" id="tenant_info_save_btn"><i class="fa fa-check pr-2"></i> <?php echo bkntc__('SAVE CHANGES')?></button>
	</div>
</div>

<div class="fs_separator"></div>

<div class="row m-4">

	<div class="col-xl-12 col-md-12 col-lg-12 p-3 pr-md-1">
		<div class="fs_portlet">
			<div class="fs_portlet_title"><?php echo bkntc__('Booking Page Info')?></div>
			<input id="tenant_id" type="hidden" value="<?php echo (int)$parameters['id']?>"/>
			<div class="fs_portlet_content">
				<div class="form-row">
					<div class="form-group col-md-5">
						<label><?php echo bkntc__('Company name')?></label>
						<input type="text" class="form-control" placeholder="<?php echo bkntc__('Company name')?>" id="company_name" value="<?php echo htmlspecialchars($companyName)?>">
					</div>
				</div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label><?php echo bkntc__('Privacy Policy URL')?></label>
                        <input type="url" class="form-control" placeholder="<?php echo bkntc__('Privacy Policy URL')?>" id="privacy_policy_url" value="<?php echo htmlspecialchars($parameters['info']['privacy_policy_url'] ?? '')?>">
                    </div>
                    <div class="form-group col-md-6">
                        <label><?php echo bkntc__('Terms & Conditions URL')?></label>
                        <input type="url" class="form-control" placeholder="<?php echo bkntc__('Terms & Conditions URL')?>" id="terms_conditions_url" value="<?php echo htmlspecialchars($parameters['info']['terms_conditions_url'] ?? '')?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-12">
                        <label><?php echo bkntc__('Footer first column')?></label>
                        <div class="tenant_body_rt">
                            <textarea name="footer_first_column" id="footer_first_column" class="editor_tenant_info" cols="30" rows="10"><?php echo htmlspecialchars($parameters['info']['footer_first_column'])?></textarea>
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-12">
                        <label><?php echo bkntc__('Footer second column')?></label>
                        <div class="tenant_body_rt">
                            <textarea name="footer_second_column" class="editor_tenant_info" id="footer_second_column" cols="30" rows="10"><?php echo htmlspecialchars($parameters['info']['footer_second_column'])?></textarea>
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-12">
                        <label><?php echo bkntc__('Footer third column')?></label>
                        <div class="tenant_body_rt">
                            <textarea name="footer_third_column" class="editor_tenant_info" id="footer_third_column" cols="30" rows="10"><?php echo htmlspecialchars($parameters['info']['footer_third_column'])?></textarea>
                        </div>
                    </div>
                </div>
<!--                <div class="form-row">-->
<!--                    <div class="form-group col-md-12">-->
<!--                        <label>--><?php //echo bkntc__('Voettekst vierde kolom')?><!--</label>-->
<!--                        <div class="tenant_body_rt">-->
<!--                            <textarea name="footer_fourth_column" class="editor_tenant_info" id="footer_fourth_column" cols="30" rows="10">--><?php //echo htmlspecialchars($parameters['info']['footer_fourth_column'])?><!--</textarea>-->
<!--                        </div>-->
<!--                    </div>-->
<!--                </div>-->
<!--				<div class="form-row">-->
<!--					<div class="form-group col-md-12">-->
<!--						<label>--><?php //echo bkntc__('Voettekst')?><!--</label>-->
<!--						<div class="tenant_body_rt">-->
<!--							<textarea name="tenant_footer_text" id="tenant_footer_text" cols="30" rows="10">--><?php //echo htmlspecialchars($parameters['info']['tenant_footer_text'])?><!--</textarea>-->
<!--						</div>-->
<!--					</div>-->
<!--				</div>-->

			</div>
		</div>
	</div>

</div>
