<div id="centralized_RoomsConfig" hasform="true">
			<div class="centralized_RoomsConfig_C">
				<div class="a fl">
					<ul>
						<li class="head_TJ">房间信息</li>
						<li>总计：<span class="red"><?php echo $tpl_data['room_count'];?></span>间</li>
						<li>已配置：<span class="red"><?php echo $tpl_data['room_config_count'];?></span>间</li>
						<li>未配置：<span class="red"><?php echo $tpl_data['no_config'];?></span>间</li>
					</ul>
				</div>
				<div class="b fr">
					<ul>
						<li><span  class="checkBox checked"><span class="ifont1">&#xe60c;</span></span>隐藏已配置房间<a parent_url="<?php echo $tpl_data['parent_url'];?>"  href="javascript:;" class="btn btn2">保存</a></li>
					</ul>
				</div>
			</div>
			<div class="centralized_RoomsConfig_D">
				<div class="a">
					<div class="a_1"><?php echo $tpl_data['flat_data']['flat_name'];?></div>
					<!--[if ie 6]>
					<div style="height: 0; overflow: hidden;"></div>
					<![endif]--> 
					<div class="a_2 fl">
						<ul>
						<?php foreach ($tpl_data['floor'] as $lk => $lv):?>
							<li <?php echo $lk == 0 ? 'class="current"' : '';?>>
							<?php foreach ($lv['data'] as $lvk => $lvval):?>
								<span class="floorNum"><?php echo $lvval;?></span><?php echo $lvk < count($lv['data']) -1 ? '\\' : '';?>
							<?php endforeach;?>
							<div class="floor_Detail">
								<dl>
								<?php foreach ($lv['count'] as $fk => $fv):?>
									<dd><span class="name">已租：</span><span class="num"><?php echo $fv['rent'];?></span></dd>
									<dd><span class="name">未租：</span><span class="num"><?php echo $fv['not_rent'];?></span></dd>
									<dd><span class="name">预订：</span><span class="num"><?php echo $fv['yd'];?></span></dd>
									<dd><span class="name">停用：</span><span class="num"><?php echo $fv['stop'];?></span></dd>
									<dd><span class="name">预退：</span><span class="num"><?php echo $fv['yytd'];?></span></dd>
								<?php endforeach;?>
								</dl>
							</div>
							</li>
					<?php endforeach;?>
						</ul>
						<div class="clear"></div>
					</div>
					<div class="clear"></div>
				</div>
				<div class="b">
					<ul>
					<?php foreach ($tpl_data['list_data'] as $listkey => $listval):?>
						<li style="z-index:{{$listkey}};">
							<span class="floorNum fl"><span class="floor_Num"><?php echo $listkey;?></span> 楼</span>
							<dl class="fl">
								<?php foreach ($listval as $lvkey => $lvval):?>
								<dd<?php echo isset($lvval['room_id']) && $lvval['room_id'] == 0 ? 'class="choosed"' : '';?>>
									<span class="romm_NUM fl"><?php echo $lvval['custom_number'];?></span>
									<span class="checkBox">
										<label class="checked"><span class="gou ifont ifont1" <?php echo (isset($lvval['template_id']) && $lvval['template_id'] > 0 && $lvval['template_id'] == $tpl_data['template_id']) ? 'style="display: inline;"' : '';?>>&#xe60c;</span></label>
										<input checked="checked" <?php echo (isset($lvval['template_id']) && $lvval['template_id'] > 0 && $lvval['template_id'] == $tpl_data['template_id']) ? 'checked="checked"' : '';?> value="<?php echo $lvval['room_focus_id'];?>" type="checkbox"/>
									</span>
									<?php if(isset($lvval['room_id']) && $lvval['room_id'] == 0):?>
									<span class="room-configed"><p>配置名称：<?php echo $lvval['template_name'];?></p><p>房间户型：<?php echo $lvval['house_type'];?></p></span>
									<?php endif;?>
								</dd>
								<?php endforeach;?>
							</dl>
						</li>
					<?php endforeach;?>
					</ul>
				</div>
			</div>
</div>
