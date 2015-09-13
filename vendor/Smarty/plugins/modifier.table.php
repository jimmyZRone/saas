<?php
function smarty_modifier_table($data)
{
	if (is_array($data))
	{
		$str="<table width=\"800\" border=\"1\">";
		if (is_array($data['row']) && !empty($data['row']))
		{
			$str.="<tr>";
			foreach ($data['row'] as $rkey)
			{
				$str.= "<th scope=\"col\">".$rkey."</th>";
			}
			$str.="</tr>";
		}
		if (is_array($data['data']) && !empty($data['data']))
		{
			foreach ($data['data'] as $dkey)
			{
				$str.="<tr>";
					foreach ($dkey as $dkey_val)
					{
						$str.="<td>".$dkey_val."</td>";	
					}
				$str.="</tr>";
			}
		}
		$str.="</table>";
		return $str;
	}	
	return false;
}