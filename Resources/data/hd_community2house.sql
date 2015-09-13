insert into
housing_distribution
(user_id,source,source_id)
(select
	u.user_id as user_id,4 as source,h.house_id as source_id
from housing_distribution as hd
join house as h on h.community_id = hd.source_id and hd.source = 1
join `user` as u on u.user_id = hd.user_id)