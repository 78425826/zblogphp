<?php
/**
 * Z-Blog with PHP
 * @author 
 * @copyright (C) RainbowSoft Studio
 * @version 2.0 2013-06-14
 */





function VerifyLogin(){
	global $zbp;

	if (isset($zbp->membersbyname[GetVars('username','POST')])) {
		if($zbp->Verify_MD5(GetVars('username','POST'),GetVars('password','POST'))){
			$un=GetVars('username','POST');
			$ps=md5($zbp->user->Password . $zbp->path);
			if(GetVars('savedate')==0){
				setcookie("username", $un,0,$zbp->cookiespath);
				setcookie("password", $ps,0,$zbp->cookiespath);
			}else{
				setcookie("username", $un, time()+3600*24*GetVars('savedate','POST'),$zbp->cookiespath);
				setcookie("password", $ps, time()+3600*24*GetVars('savedate','POST'),$zbp->cookiespath);
			}
			return true;
		}else{
			$zbp->ShowError(8);
		}
	}else{
		$zbp->ShowError(8);
	}
}


function Logout(){
	global $zbp;

	setcookie('username', '',time() - 3600,$zbp->cookiespath);
	setcookie('password', '',time() - 3600,$zbp->cookiespath);

}






################################################################################################################
function ViewList($page,$cate,$auth,$date,$tags){
	global $zbp;
	foreach ($GLOBALS['Filter_Plugin_ViewList_Begin'] as $fpname => &$fpsignal) {
		$fpreturn=$fpname($page,$cate,$auth,$date,$tags);
		if ($fpsignal==PLUGIN_EXITSIGNAL_RETURN) {return $fpreturn;}
	}

	$type='index';
	if($cate!==null)$type='category';
	if($auth!==null)$type='author';
	if($date!==null)$type='date';
	if($tags!==null)$type='tag';

	$category=null;
	$author=null;
	$datetime=null;
	$tag=null;


	$w=array();
	$w[]=array('=','log_Istop',0);
	$w[]=array('=','log_Status',0);

	$page=(int)GetVars('page','GET')==0?1:(int)GetVars('page','GET');

	$articles_top=array();
	$articles=array();
	if($type=='index' && $page==1){
		$articles_top=$zbp->GetArticleList(
			array('*'),
			array(array('=','log_Istop',1),array('=','log_Status',0)),
			array('log_PostTime'=>'DESC'),
			null,
			null
		);
	}

	switch ($type) {
		########################################################################################################
		case 'index':
	$pagebar=new Pagebar($zbp->option['ZC_INDEX_REGEX']);
	$pagebar->Count=$zbp->cache->normal_article_nums;
	$category=new Metas;
	$author=new Metas;
	$datetime=new Metas;
	$tag=new Metas;
	$template=$zbp->option['ZC_INDEX_DEFAULT_TEMPLATE'];
	if($page==1){
		$zbp->title=$zbp->subname;
	}else{
		$zbp->title=str_replace('%num%', $page, $zbp->lang['msg']['number_page']);
	}
			break;
		########################################################################################################
		case 'category':
	$pagebar=new Pagebar($zbp->option['ZC_CATEGORY_REGEX']);
	$author=new Metas;
	$datetime=new Metas;
	$tag=new Metas;

	$category=new Category;
	if(strpos($zbp->option['ZC_CATEGORY_REGEX'],'{%id%}')!==false){
		$category=$zbp->GetCategoryByID($cate);
	}
	if(strpos($zbp->option['ZC_CATEGORY_REGEX'],'{%alias%}')!==false){
		$category=$zbp->GetCategoryByAliasOrName($cate);
	}
	if($category->ID==0)$zbp->ShowError(2);
	if($page==1){
		$zbp->title=$category->Name;
	}else{
		$zbp->title=$category->Name . ' ' . str_replace('%num%', $page, $zbp->lang['msg']['number_page']);
	}
	$template=$category->Template;
	$w[]=array('=','log_CateID',$category->ID);
	$pagebar->Count=$category->Count;
	$pagebar->UrlRule->Rules['{%id%}']=$category->ID;
	$pagebar->UrlRule->Rules['{%alias%}']=$category->Alias==''?urlencode($category->Name):$category->Alias;
			break;
		########################################################################################################
		case 'author':
	$pagebar=new Pagebar($zbp->option['ZC_AUTHOR_REGEX']);
	$category=new Metas;
	$datetime=new Metas;
	$tag=new Metas;

	$author=new Member;
	if(strpos($zbp->option['ZC_AUTHOR_REGEX'],'{%id%}')!==false){
		$author=$zbp->GetMemberByID($auth);
	}
	if(strpos($zbp->option['ZC_AUTHOR_REGEX'],'{%alias%}')!==false){
		$author=$zbp->GetMemberByAliasOrName($auth);
	}
	if($author->ID==0)$zbp->ShowError(2);
	if($page==1){
		$zbp->title=$author->Name;
	}else{
		$zbp->title=$author->Name . ' ' . str_replace('%num%', $page, $zbp->lang['msg']['number_page']);
	}
	$template=$author->Template;
	$w[]=array('=','log_AuthorID',$author->ID);
	$pagebar->Count=$author->Articles;
	$pagebar->UrlRule->Rules['{%id%}']=$author->ID;
	$pagebar->UrlRule->Rules['{%alias%}']=$author->Alias==''?urlencode($author->Name):$author->Alias;
			break;
		########################################################################################################
		case 'date':
	$pagebar=new Pagebar($zbp->option['ZC_DATE_REGEX']);
	$category=new Metas;
	$author=new Metas;
	$tag=new Metas;
	$datetime=strtotime($date);

	$datetitle=str_replace(array('%y%','%m%'), array(date('Y',$datetime),date('n',$datetime)), $zbp->lang['msg']['year_month']);
	if($page==1){
		$zbp->title=$datetitle;
	}else{
		$zbp->title=$datetitle . ' ' . str_replace('%num%', $page, $zbp->lang['msg']['number_page']);
	}

	$zbp->modulesbyfilename['calendar']->Content=BuildModule_calendar(date('Y',$datetime).'-'.date('n',$datetime));

	$template=$zbp->option['ZC_INDEX_DEFAULT_TEMPLATE'];
	$w[]=array('BETWEEN','log_PostTime',$datetime,strtotime('+1 month',$datetime));
	$pagebar->UrlRule->Rules['{%date%}']=$date;
	$datetime=Metas::ConvertArray(getdate($datetime));
			break;
		########################################################################################################
		case 'tag':
	$pagebar=new Pagebar($zbp->option['ZC_TAGS_REGEX']);
	$category=new Metas;
	$author=new Metas;
	$datetime=new Metas;
	$tag=new Tag;
	if(strpos($zbp->option['ZC_TAGS_REGEX'],'{%id%}')!==false){
		$tag=$zbp->GetTagByID($tags);
	}
	if(strpos($zbp->option['ZC_TAGS_REGEX'],'{%alias%}')!==false){
		$tag=$zbp->GetTagByAliasOrName($tags);
	}
	if($tag->ID==0)$zbp->ShowError(2);

	$template=$tag->Template;
	$w[]=array('LIKE','log_Tag','%{'.$tag->ID.'}%');
	$pagebar->UrlRule->Rules['{%id%}']=$tag->ID;
	$pagebar->UrlRule->Rules['{%alias%}']=$tag->Alias==''?urlencode($tag->Name):$tag->Alias;
			break;
	}

	$pagebar->PageCount=$zbp->displaycount;
	$pagebar->PageNow=$page;
	$pagebar->PageBarCount=$zbp->pagebarcount;
	$pagebar->UrlRule->Rules['{%page%}']=$page;

	$articles=$zbp->GetArticleList(
		array('*'),
		$w,
		array('log_PostTime'=>'DESC'),
		array(($pagebar->PageNow-1) * $pagebar->PageCount,$pagebar->PageCount),
		array('pagebar'=>$pagebar)
	);

	$zbp->template->SetTags('title',$zbp->title);
	$zbp->template->SetTags('articles',array_merge($articles_top,$articles));
	if($pagebar->PageAll==0)$pagebar=null;
	$zbp->template->SetTags('pagebar',$pagebar);
	$zbp->template->SetTags('type',$type);
	$zbp->template->SetTags('page',$page);

	$zbp->template->SetTags('date',$datetime);
	$zbp->template->SetTags('tag',$tag);
	$zbp->template->SetTags('author',$type);
	$zbp->template->SetTags('category',$category);
	
	$zbp->template->SetTemplate($template);

	foreach ($GLOBALS['Filter_Plugin_ViewList_Template'] as $fpname => &$fpsignal) {
		$fpreturn=$fpname($zbp->template);
	}

	$zbp->template->Display();

}





function ViewPost($id,$alias){
	global $zbp;
	foreach ($GLOBALS['Filter_Plugin_ViewPost_Begin'] as $fpname => &$fpsignal) {
		$fpreturn=$fpname($id,$alias);
		if ($fpsignal==PLUGIN_EXITSIGNAL_RETURN) {return $fpreturn;}
	}

	$w=array();

	if($id!==null){
		$w[]=array('=','log_ID',$id);
	}elseif($alias!==null){
		$w[]=array('array',array(array('log_Alias',$alias),array('log_Title',$alias)));
	}else{
		$zbp->ShowError(2);
		die();
	}

	$w[]=array('=','log_Status',0);
	$articles=$zbp->GetPostList(
		array('*'),
		$w,
		null,
		array(1),
		null
	);
	if(count($articles)==0){
		$zbp->ShowError(2);
		die();
	}

	$article = $articles[0];
	if($zbp->option['ZC_COMMENT_TURNOFF']){
		$article->IsLock = true;
	}

	if($article->Type==0){
		$zbp->LoadTagsByIDString($article->Tag);
	}

	$article->ViewNums +=1;
	$sql = $zbp->db->sql->Update($zbp->table['Post'],array('log_ViewNums'=>$article->ViewNums),array(array('=','log_ID',$article->ID)));
	$zbp->db->Update($sql);


	$pagebar=new Pagebar('javascript:GetComments(\''.$article->ID.'\',\'{%page%}\')',false);
	$pagebar->PageCount=$zbp->commentdisplaycount;
	$pagebar->PageNow=1;
	$pagebar->PageBarCount=$zbp->pagebarcount;

	$comments=array();

	$comments=$zbp->GetCommentList(
		array('*'),
		array(array('=','comm_RootID',0),array('=','comm_IsChecking',0),array('=','comm_LogID',$article->ID)),
		array('comm_ID'=>($zbp->option['ZC_COMMENT_REVERSE_ORDER']?'DESC':'ASC')),
		array(($pagebar->PageNow-1) * $pagebar->PageCount,$pagebar->PageCount),
		array('pagebar'=>$pagebar)
	);
	$rootid=array();
	foreach ($comments as &$comment) {
		$rootid[]=array('comm_RootID',$comment->ID);
	}
	$comments2=$zbp->GetCommentList(
		array('*'),
		array(array('array',$rootid),array('=','comm_IsChecking',0)),
		array('comm_ID'=>($zbp->option['ZC_COMMENT_REVERSE_ORDER']?'DESC':'ASC')),
		null,
		null
	);

	foreach ($comments as &$comment){
		$comment->Content=TransferHTML($comment->Content,'[enter]') . '<label id="AjaxComment'.$comment->ID.'"></label>';
	}
	foreach ($comments2 as &$comment){
		$comment->Content=TransferHTML($comment->Content,'[enter]') . '<label id="AjaxComment'.$comment->ID.'"></label>';
	}

	$zbp->template->SetTags('title',$article->Title);
	$zbp->template->SetTags('article',$article);
	$zbp->template->SetTags('type',$article->type=0?'article':'page');
	$zbp->template->SetTags('page',1);
	if($pagebar->PageAll==0||$pagebar->PageAll==1)$pagebar=null;
	$zbp->template->SetTags('pagebar',$pagebar);
	$zbp->template->SetTags('comments',$comments);
	
	$zbp->template->SetTemplate($article->Template);

	foreach ($GLOBALS['Filter_Plugin_ViewPost_Template'] as $fpname => &$fpsignal) {
		$fpreturn=$fpname($zbp->template);
	}

	$zbp->template->Display();
}





function ViewComments($postid,$page){
	global $zbp;

	$post = New Post;
	$post->LoadInfoByID($postid);
	$page=$page==0?1:$page;
	$template='comments';

	$pagebar=new Pagebar('javascript:GetComments(\''.$post->ID.'\',\'{%page%}\')');
	$pagebar->PageCount=$zbp->commentdisplaycount;
	$pagebar->PageNow=$page;
	$pagebar->PageBarCount=$zbp->pagebarcount;

	$comments=array();

	$comments=$zbp->GetCommentList(
		array('*'),
		array(array('=','comm_RootID',0),array('=','comm_IsChecking',0),array('=','comm_LogID',$post->ID)),
		array('comm_ID'=>($zbp->option['ZC_COMMENT_REVERSE_ORDER']?'DESC':'ASC')),
		array(($pagebar->PageNow-1) * $pagebar->PageCount,$pagebar->PageCount),
		array('pagebar'=>$pagebar)
	);
	$rootid=array();
	foreach ($comments as $comment) {
		$rootid[]=array('comm_RootID',$comment->ID);
	}
	$comments2=$zbp->GetCommentList(
		array('*'),
		array(array('array',$rootid),array('=','comm_IsChecking',0)),
		array('comm_ID'=>($zbp->option['ZC_COMMENT_REVERSE_ORDER']?'DESC':'ASC')),
		null,
		null
	);

	foreach ($comments as &$comment){
		$comment->Content=TransferHTML($comment->Content,'[enter]') . '<label id="AjaxComment'.$comment->ID.'"></label>';
	}
	foreach ($comments2 as &$comment){
		$comment->Content=TransferHTML($comment->Content,'[enter]') . '<label id="AjaxComment'.$comment->ID.'"></label>';
	}

	$zbp->template->SetTags('title',$zbp->title);
	$zbp->template->SetTags('article',$post);
	$zbp->template->SetTags('type','comment');
	$zbp->template->SetTags('page',$page);
	if($pagebar->PageAll==1)$pagebar=null;
	$zbp->template->SetTags('pagebar',$pagebar);
	$zbp->template->SetTags('comments',$comments);
	
	$zbp->template->SetTemplate($template);

	foreach ($GLOBALS['Filter_Plugin_ViewComments_Template'] as $fpname => &$fpsignal) {
		$fpreturn=$fpname($zbp->template);
	}

	$s=$zbp->template->Output();

	$a=explode('<label id="AjaxCommentBegin"></label>', $s);
	$s=$a[1];
	$a=explode('<label id="AjaxCommentEnd"></label>', $s);
	$s=$a[0];
	
	echo $s;

}





function ViewComment($id){
	global $zbp;

	$template='comment';
	$comment=$zbp->GetCommentByID($id);
	$post=new Post;
	$post->LoadInfoByID($comment->LogID);

	$comment->Content=TransferHTML($comment->Content,'[enter]') . '<label id="AjaxComment'.$comment->ID.'"></label>';

	$zbp->template->SetTags('title',$zbp->title);
	$zbp->template->SetTags('comment',$comment);
	$zbp->template->SetTags('article',$post);
	$zbp->template->SetTags('type','comment');
	$zbp->template->SetTags('page',1);
	$zbp->template->SetTemplate($template);

	$zbp->template->Display();

}







################################################################################################################
function PostArticle(){
	global $zbp;
	if(!isset($_POST['ID']))return ;

	if(isset($_POST['Tag'])){
		$_POST['Tag']=PostArticle_CheckTagAndConvertIDtoString($_POST['Tag']);
	}
	if(isset($_POST['Content'])){
		$_POST['Content']=str_replace('<hr class="more" />', '<!--more-->', $_POST['Content']);
		$_POST['Content']=str_replace('<hr class="more"/>', '<!--more-->', $_POST['Content']);
		if(strpos($_POST['Content'], '<!--more-->')!==false){
			$_POST['Intro']=GetValueInArray(explode('<!--more-->',$_POST['Content']),0);
		}else{
			if(isset($_POST['Intro'])&&$_POST['Intro']==''){
				$_POST['Intro']=substr($_POST['Content'], 0,250);
				if(strpos($_POST['Intro'],'<')!==false){
					$_POST['Intro']=CloseTags($_POST['Intro']);
				}
			}
		}
	}

	if(!isset($_POST['AuthorID'])){
		$_POST['AuthorID']=$zbp->user->ID;
	}else{
		if(($_POST['AuthorID']!=$zbp->user->ID )&&(!$zbp->CheckRights('ArticleAll'))){
			$_POST['AuthorID']=$zbp->user->ID;
		}
	}

	if(isset($_POST['PostTime'])){
		$_POST['PostTime']=strtotime($_POST['PostTime']);
	}

	if(!$zbp->CheckRights('ArticleAll')){
		unset($_POST['IsTop']);
	}

	$article = new Post();
	$pre_author=null;
	$pre_tag=null;
	$pre_category=null;
	if(GetVars('ID','POST') == 0){
		if(!$zbp->CheckRights('ArticlePub')){$article->Status=ZC_POST_STATUS_AUDITING;}
	}else{
		$article->LoadInfoByID(GetVars('ID','POST'));
		if(($article->AuthorID!=$zbp->user->ID )&&(!$zbp->CheckRights('ArticleAll'))){$zbp->ShowError(11);}
		if((!$zbp->CheckRights('ArticlePub'))&&($article->Status==ZC_POST_STATUS_AUDITING)){$_POST['Status']=ZC_POST_STATUS_AUDITING;}
		$pre_author=$article->AuthorID;
		$pre_tag=$article->Tag;
		$pre_category=$article->CateID;
	}
	$article->Type = ZC_POST_TYPE_ARTICLE;

	foreach ($zbp->datainfo['Post'] as $key => $value) {
		if($key=='ID')continue;
		if($key=='Meta')continue;
		if( isset($_POST[$key]) ){
			$article->$key    = GetVars($key,'POST');
		}
	}

	FilterArticle($article);
	FilterMeta($article);

	foreach ($GLOBALS['Filter_Plugin_PostArticle_Core'] as $fpname => &$fpsignal) {
		$fpreturn=$fpname($article);
	}

	$article->Save();

	CountTagArrayString($pre_tag . $article->Tag);
	CountMemberArray(array($pre_author,$article->AuthorID));
	CountCategoryArray(array($pre_category,$article->CateID));
	CountPostArray(array($article->ID));
	CountNormalArticleNums();

	$zbp->AddBuildModule('previous');
	$zbp->AddBuildModule('calendar');
	$zbp->AddBuildModule('comments');
	$zbp->AddBuildModule('archives');

	return true;
}


function DelArticle(){
	global $zbp;

	$id=(int)GetVars('id','GET');

	$article = new Post();
	$article->LoadInfoByID($id);
	if($article->ID>0){

		if(!$zbp->CheckRights('ArticleAll')&&$article->AuthorID!=$zbp->user->ID)$zbp->ShowError(22);

		$pre_author=$article->AuthorID;
		$pre_tag=$article->Tag;
		$pre_category=$article->CateID;

		$article->Del();

		DelArticle_Comments($article->ID);

		CountTagArrayString($pre_tag);
		CountMemberArray(array($pre_author));
		CountCategoryArray(array($pre_category));
		CountNormalArticleNums();

		$zbp->AddBuildModule('previous');
		$zbp->AddBuildModule('calendar');
		$zbp->AddBuildModule('comments');
		$zbp->AddBuildModule('archives');

	}else{
		
	}
	return true;
}



function PostArticle_CheckTagAndConvertIDtoString($tagnamestring){
	global $zbp;
	$s='';
	$tagnamestring=str_replace(';', ',', $tagnamestring);
	$tagnamestring=str_replace('，', ',', $tagnamestring);
	$tagnamestring=str_replace('、', ',', $tagnamestring);
	$tagnamestring=strip_tags($tagnamestring);
	$tagnamestring=trim($tagnamestring);
	if($tagnamestring=='')return '';
	if($tagnamestring==',')return '';		
	$a=explode(',', $tagnamestring);
	$b=array_unique($a);
	$b=array_slice($b, 0, 20);
	$c=array();

	$t=$zbp->LoadTagsByNameString(GetVars('Tag','POST'));
	foreach ($t as $key => $value) {
		$c[]=$key;
	}
	$d=array_diff($b,$c);
	if($zbp->CheckRights('TagNew')){
		foreach ($d as $key) {
			$tag = new Tag;
			$tag->Name = $key;
			FilterTag($tag);
			$tag->Save();
			$zbp->tags[$tag->ID]=$tag;
			$zbp->tagsbyname[$tag->Name]=&$zbp->tags[$tag->ID];
		}
	}

	foreach ($a as $key) {
		if(!isset($zbp->tagsbyname[$key]))continue;
		$s .= '{' . $zbp->tagsbyname[$key]->ID . '}';
	}
	return $s;
}



function DelArticle_Comments($id){
	global $zbp;

	$sql = $zbp->db->sql->Delete($zbp->table['Comment'],array(array('=','comm_LogID',$id)));
	$zbp->db->Delete($sql);
}



################################################################################################################
function PostPage(){
	global $zbp;
	if(!isset($_POST['ID']))return ;

	if(isset($_POST['PostTime'])){
		$_POST['PostTime']=strtotime($_POST['PostTime']);
	}	

	if(!isset($_POST['AuthorID'])){
		$_POST['AuthorID']=$zbp->user->ID;
	}else{
		if(($_POST['AuthorID']!=$zbp->user->ID )&&(!$zbp->CheckRights('PageAll'))){
			$_POST['AuthorID']=$zbp->user->ID;
		}
	}

	$article = new Post();
	$pre_author=null;
	if(GetVars('ID','POST') == 0){
	}else{
		$article->LoadInfoByID(GetVars('ID','POST'));
		if(($article->AuthorID!=$zbp->user->ID )&&(!$zbp->CheckRights('PageAll'))){$zbp->ShowError(11);}
		$pre_author=$article->AuthorID;
	}
	$article->Type = ZC_POST_TYPE_PAGE;

	foreach ($zbp->datainfo['Post'] as $key => $value) {
		if($key=='ID')continue;
		if($key=='Meta')continue;
		if( isset($_POST[$key]) ){
			$article->$key    = GetVars($key,'POST');
		}
	}

	FilterArticle($article);
	FilterMeta($article);

	foreach ($GLOBALS['Filter_Plugin_PostPage_Core'] as $fpname => &$fpsignal) {
		$fpreturn=$fpname($cmt);
	}

	$article->Save();

	CountMemberArray(array($pre_author,$article->AuthorID));
	CountPostArray(array($article->ID));

	$zbp->AddBuildModule('comments');

	if(GetVars('AddNavbar','POST')==0)$zbp->DelItemToNavbar('page',$article->ID);
	if(GetVars('AddNavbar','POST')==1)$zbp->AddItemToNavbar('page',$article->ID,$article->Title,$article->Url);

	return true;
}

function DelPage(){
	global $zbp;

	$id=(int)GetVars('id','GET');

	$article = new Post();
	$article->LoadInfoByID($id);
	if($article->ID>0){

		if(!$zbp->CheckRights('PageAll')&&$article->AuthorID!=$zbp->user->ID)$zbp->ShowError(22);

		$pre_author=$article->AuthorID;

		$article->Del();

		DelArticle_Comments($article->ID);

		CountMemberArray(array($pre_author));

		$zbp->AddBuildModule('comments');

	}else{
		
	}
	return true;
}










################################################################################################################
function PostComment(){
	global $zbp;

	$_POST['LogID'] = $_GET['postid'];

	$replyid=(integer)GetVars('replyid','POST');

	if($replyid==0){
		$_POST['RootID'] = 0;
		$_POST['ParentID'] = 0;
	}else{
		$_POST['ParentID'] = $replyid;
		$c = new Comment();
		$c->LoadInfoByID($replyid);
		if($c->Level==3){
			$zbp->ShowError(52);
		}
		if($c->RootID==0){
			$_POST['RootID'] = $c->ID;
		}else{
			$_POST['RootID'] = $c->RootID;
		}
	}

	$_POST['AuthorID'] = $zbp->user->ID;
	$_POST['Name'] = $_POST['name'];
	$_POST['Email'] = $_POST['email'];	
	$_POST['HomePage'] = $_POST['homepage'];
	$_POST['Content'] = $_POST['content'];
	$_POST['PostTime'] = Time();
	$_POST['IP'] = GetGuestIP();	
	$_POST['Agent'] = GetGuestAgent();

	$cmt = new Comment();

	foreach ($zbp->datainfo['Comment'] as $key => $value) {
		if($key=='ID')continue;
		if($key=='Meta')continue;
		if( isset($_POST[$key]) ){
			$cmt->$key    = GetVars($key,'POST');
		}
	}

	foreach ($GLOBALS['Filter_Plugin_PostComment_Core'] as $fpname => &$fpsignal) {
		$fpreturn=$fpname($cmt);
	}

	FilterComment($cmt);

	if($cmt->IsThrow==false){

		$cmt->Save();

		if($cmt->IsChecking==false){

			CountPostArray(array($cmt->LogID));

			$zbp->AddBuildModule('comments');

			$zbp->comments[$cmt->ID]=$cmt;
			
			if(GetVars('isajax','POST')){
				ViewComment($cmt->ID);
			}

			return true;

		}else{

			$zbp->ShowError(53);

		}

	}else{

		$zbp->ShowError(14);

	}
}


function DelComment(){
	global $zbp;

	$id=(int)GetVars('id','GET');
	$cmt=$zbp->GetCommentByID($id);
	if($cmt->ID>0){

		$comments=$zbp->GetCommentList(
			array('*'),
			array(array('=','comm_LogID',$cmt->LogID)),
			null,
			null,
			null
		);

		DelComment_Children($cmt->ID);

		$cmt->Del();

		$zbp->AddBuildModule('comments');
	}
	return true;
}

function DelComment_Children($id){
	global $zbp;

	$cmt=$zbp->GetCommentByID($id);

	foreach ($cmt->Comments as $comment) {
		if(Count($comment->Comments)>0){
			DelComment_Children($comment->ID);
		}
		$comment->Del();
	}

}


function CheckComment(){
	global $zbp;

	$id=(int)GetVars('id','GET');
	$ischecking=(bool)GetVars('ischecking','GET');

	$cmt = $zbp->GetCommentByID($id);
	$cmt->IsChecking=$ischecking;

	$cmt->Save();

	CountPostArray(array($cmt->LogID));
	$zbp->AddBuildModule('comments');
}





################################################################################################################
function PostCategory(){
	global $zbp;
	if(!isset($_POST['ID']))return ;

	$parentid=(int)GetVars('ParentID','POST');
	if($parentid>0){
		if($zbp->categorys[$parentid]->Level>2){
			$_POST['ParentID']='0';
		}
	}

	$cate = new Category();
	if(GetVars('ID','POST') == 0){
	}else{
		$cate->LoadInfoByID(GetVars('ID','POST'));
	}

	foreach ($zbp->datainfo['Category'] as $key => $value) {
		if($key=='ID')continue;
		if($key=='Meta')continue;
		if( isset($_POST[$key]) ){
			$cate->$key    = GetVars($key,'POST');
		}
	}

	FilterMeta($cate);

	FilterCategory($cate);

	CountCategory($cate);

	$cate->Save();

	$zbp->AddBuildModule('catalog');

	if(GetVars('AddNavbar','POST')==0)$zbp->DelItemToNavbar('category',$cate->ID);
	if(GetVars('AddNavbar','POST')==1)$zbp->AddItemToNavbar('category',$cate->ID,$cate->Name,$cate->Url);

	return true;
}



function DelCategory(){
	global $zbp;

	$id=(int)GetVars('id','GET');
	$cate=$zbp->GetCategoryByID($id);
	if($cate->ID>0){
		DelCategory_Articles($cate->ID);		
		$cate->Del();
		$zbp->AddBuildModule('catalog');
		$zbp->DelItemToNavbar('category',$cate->ID);
	}
	return true;
}


function DelCategory_Articles($id){
	global $zbp;

	$sql = $zbp->db->sql->Update($zbp->table['Post'],array('log_CateID'=>0),array(array('=','log_CateID',$id)));
	$zbp->db->Update($sql);
}





################################################################################################################
function PostTag(){
	global $zbp;
	if(!isset($_POST['ID']))return ;

	$tag = new Tag();
	if(GetVars('ID','POST') == 0){
	}else{
		$tag->LoadInfoByID(GetVars('ID','POST'));
	}

	foreach ($zbp->datainfo['Tag'] as $key => $value) {
		if($key=='ID')continue;
		if($key=='Meta')continue;
		if( isset($_POST[$key]) ){
			$tag->$key    = GetVars($key,'POST');
		}
	}

	FilterMeta($tag);

	FilterTag($tag);

	CountTag($tag);

	$tag->Save();

	if(GetVars('AddNavbar','POST')==0)$zbp->DelItemToNavbar('tag',$tag->ID);
	if(GetVars('AddNavbar','POST')==1)$zbp->AddItemToNavbar('tag',$tag->ID,$tag->Name,$tag->Url);

	return true;
}


function DelTag(){
	global $zbp;

	$tagid=(int)GetVars('id','GET');
	$tag=$zbp->GetTagByID($tagid);
	if($tag->ID>0){
		$tag->Del();
		$zbp->DelItemToNavbar('tag',$tag->ID);
	}
	return true;
}







################################################################################################################
function PostMember(){
	global $zbp;
	if(!isset($_POST['ID']))return ;

	if(!$zbp->CheckRights('MemberAll')){
		unset($_POST['Level']);
		unset($_POST['Name']);
	}
	if(isset($_POST['Password'])){
		if($_POST['Password']==''){
			unset($_POST['Password']);
		}else{
			if(strlen($_POST['Password'])<$zbp->option['ZC_PASSWORD_MIN']||strlen($_POST['Password'])>$zbp->option['ZC_PASSWORD_MAX']){
				$zbp->ShowError(54);
			}
			if(!CheckRegExp($_POST['Password'],'[password]')){
				$zbp->ShowError(54);
			}
			$_POST['Password']=Member::GetPassWordByGuid($_POST['Password'],$_POST['Guid']);
		}
	}

	if(isset($_POST['Name'])){
		if(isset($zbp->membersbyname[$_POST['Name']])){
			if($zbp->membersbyname[$_POST['Name']]->ID<>$_POST['ID']){
				$zbp->ShowError(62);
			}
		}
	}

	$mem = new Member();
	if(GetVars('ID','POST') == 0){
		if(isset($_POST['Password'])==false||$_POST['Password']==''){
			$zbp->ShowError(73);
		}
		$_POST['IP']=GetGuestIP();
	}else{
		$mem->LoadInfoByID(GetVars('ID','POST'));
	}

	foreach ($zbp->datainfo['Member'] as $key => $value) {
		if($key=='ID')continue;
		if($key=='Meta')continue;
		if( isset($_POST[$key]) ){
			$mem->$key    = GetVars($key,'POST');
		}
	}

	FilterMeta($mem);
	FilterMember($mem);

	CountMember($mem);

	$mem->Save();
	return true;
}

function DelMember(){
	global $zbp;

	$id=(int)GetVars('id','GET');
	$m=$zbp->GetMemberByID($id);
	if($m->ID>0 && $m->ID<>$zbp->user->ID){
		DelMember_AllData($id);		
		$m->Del();
	}else{
		return false;
	}
	return true;
}


function DelMember_AllData($id){

}





################################################################################################################
function PostModule(){
	global $zbp;
	if(!isset($_POST['ID']))return ;
	if(!GetVars('FileName','POST')){
		$_POST['FileName']='mod' . rand(1000,2000);
	}else{
		$_POST['FileName']=strtolower($_POST['FileName']);
	}
	if(!GetVars('HtmlID','POST')){
		$_POST['HtmlID']=$_POST['FileName'];
	}
	if(isset($_POST['MaxLi'])){
		$_POST['MaxLi']=(integer)$_POST['MaxLi'];
	}
	if(!isset($_POST['Type'])){
		$_POST['Type']='div';
	}	
	if(isset($_POST['Content'])){
		if($_POST['Type']!='div'){
			$_POST['Content']=str_replace(array("\r","\n"), array('',''), $_POST['Content']);
		}
	}
	if(isset($_POST['Source'])){
		if($_POST['Source']=='theme'){
			$c=GetVars('Content','POST');
			$f=$zbp->usersdir . 'theme/' . $zbp->theme . '/include/' . GetVars('FileName','POST') . '.php';
			@file_put_contents($f, $c);
			return true;
		}
	}
	$mod = new Module();
	if(GetVars('ID','POST') == 0){

	}else{
		$mod->LoadInfoByID(GetVars('ID','POST'));
	}

	foreach ($zbp->datainfo['Module'] as $key => $value) {
		if($key=='ID')continue;
		if($key=='Meta')continue;
		if( isset($_POST[$key]) ){
			$mod->$key    = GetVars($key,'POST');
		}
	}

	FilterModule($mod);

	$mod->Save();
	return true;
}

function DelModule(){
	global $zbp;

	$id=(int)GetVars('id','GET');
	$m=$zbp->GetModuleByID($id);
	if($m->Source<>'system'){
		$m->Del();
	}else{
		return false;
	}
	return true;
}








################################################################################################################
function PostUpload(){
	global $zbp;

	foreach ($_FILES as $key => $value) {
		if($_FILES[$key]['error']==0){
			if (is_uploaded_file($_FILES[$key]['tmp_name'])) {
				$tmp_name = $_FILES[$key]['tmp_name'];
				$name = $_FILES[$key]['name'];

				$upload = new Upload;
				$upload->Name = $_FILES[$key]['name'];
				$upload->SourceName = $_FILES[$key]['name'];
				$upload->MimeType = $_FILES[$key]['type'];
				$upload->Size = $_FILES[$key]['size'];
				$upload->AuthorID = $zbp->user->ID;

				$upload->SaveFile($_FILES[$key]['tmp_name']);
				$upload->Save();
			}
		}
	}

	CountMemberArray(array($upload->AuthorID));

}

function DelUpload(){
	global $zbp;

	$id=(int)GetVars('id','GET');
	$u=$zbp->GetUploadByID($id);
	if($zbp->CheckRights('UploadAll')||(!$zbp->CheckRights('UploadAll')&&$u->AuthorID==$zbp->user->ID)){
		$u->Del();
		CountMemberArray(array($u->AuthorID));
		$u->DelFile();		
	}else{
		return false;
	}
	return true;
}







################################################################################################################
function EnablePlugin($name){
	global $zbp;
	$zbp->option['ZC_USING_PLUGIN_LIST']=AddNameInString($zbp->option['ZC_USING_PLUGIN_LIST'],$name);
	$zbp->SaveOption();
	return $name;
}

function DisablePlugin($name){
	global $zbp;
	$zbp->option['ZC_USING_PLUGIN_LIST']=DelNameInString($zbp->option['ZC_USING_PLUGIN_LIST'],$name);
	$zbp->SaveOption();
}

function SetTheme($theme,$style){
	global $zbp;
	$oldtheme=$zbp->option['ZC_BLOG_THEME'];

	if($oldtheme!=$theme){
		$app=$zbp->LoadApp('theme',$theme);
if($app->sidebars_sidebar1|$app->sidebars_sidebar2|$app->sidebars_sidebar3|$app->sidebars_sidebar4|$app->sidebars_sidebar5){
	$s1=$zbp->option['ZC_SIDEBAR_ORDER'];
	$s2=$zbp->option['ZC_SIDEBAR2_ORDER'];
	$s3=$zbp->option['ZC_SIDEBAR3_ORDER'];
	$s4=$zbp->option['ZC_SIDEBAR4_ORDER'];
	$s5=$zbp->option['ZC_SIDEBAR5_ORDER'];
	$zbp->option['ZC_SIDEBAR_ORDER']=$app->sidebars_sidebar1;
	$zbp->option['ZC_SIDEBAR2_ORDER']=$app->sidebars_sidebar2;
	$zbp->option['ZC_SIDEBAR3_ORDER']=$app->sidebars_sidebar3;
	$zbp->option['ZC_SIDEBAR4_ORDER']=$app->sidebars_sidebar4;
	$zbp->option['ZC_SIDEBAR5_ORDER']=$app->sidebars_sidebar5;
	$zbp->cache->ZC_SIDEBAR_ORDER1=$s1;
	$zbp->cache->ZC_SIDEBAR_ORDER2=$s2;
	$zbp->cache->ZC_SIDEBAR_ORDER3=$s3;
	$zbp->cache->ZC_SIDEBAR_ORDER4=$s4;
	$zbp->cache->ZC_SIDEBAR_ORDER5=$s5;
}else{
	if($zbp->cache->ZC_SIDEBAR_ORDER1|$zbp->cache->ZC_SIDEBAR_ORDER2|$zbp->cache->ZC_SIDEBAR_ORDER3|$zbp->cache->ZC_SIDEBAR_ORDER4|$zbp->cache->ZC_SIDEBAR_ORDER5){
		$zbp->option['ZC_SIDEBAR_ORDER'] =$zbp->cache->ZC_SIDEBAR_ORDER1;
		$zbp->option['ZC_SIDEBAR2_ORDER']=$zbp->cache->ZC_SIDEBAR_ORDER2;
		$zbp->option['ZC_SIDEBAR3_ORDER']=$zbp->cache->ZC_SIDEBAR_ORDER3;
		$zbp->option['ZC_SIDEBAR4_ORDER']=$zbp->cache->ZC_SIDEBAR_ORDER4;
		$zbp->option['ZC_SIDEBAR5_ORDER']=$zbp->cache->ZC_SIDEBAR_ORDER5;
		$zbp->cache->ZC_SIDEBAR_ORDER1='';
		$zbp->cache->ZC_SIDEBAR_ORDER2='';
		$zbp->cache->ZC_SIDEBAR_ORDER3='';
		$zbp->cache->ZC_SIDEBAR_ORDER4='';
		$zbp->cache->ZC_SIDEBAR_ORDER5='';
		$zbp->SaveCache();
	}
}

	}

	$zbp->option['ZC_BLOG_THEME']=$theme;
	$zbp->option['ZC_BLOG_CSS']=$style;

	$zbp->BuildTemplate();

	$zbp->SaveOption();
}

function SetSidebar(){
	global $zbp;

	$zbp->option['ZC_SIDEBAR_ORDER'] =trim(GetVars('sidebar','POST'),'|');
	$zbp->option['ZC_SIDEBAR2_ORDER']=trim(GetVars('sidebar2','POST'),'|');
	$zbp->option['ZC_SIDEBAR3_ORDER']=trim(GetVars('sidebar3','POST'),'|');
	$zbp->option['ZC_SIDEBAR4_ORDER']=trim(GetVars('sidebar4','POST'),'|');
	$zbp->option['ZC_SIDEBAR5_ORDER']=trim(GetVars('sidebar5','POST'),'|');	
	$zbp->SaveOption();
}


function SaveSetting(){
	global $zbp;

	foreach ($_POST as $key => $value) {
		if(substr($key,0,2)!=='ZC')continue;
		if($key=='ZC_PERMANENT_DOMAIN_ENABLE'
		 ||$key=='ZC_DEBUG_MODE'
		 ||$key=='ZC_COMMENT_TURNOFF'
		 ||$key=='ZC_COMMENT_REVERSE_ORDER_EXPORT'
		){
			$zbp->option[$key]=(boolean)$value;
			continue;
		}
		if($key=='ZC_RSS2_COUNT'
		 ||$key=='ZC_UPLOAD_FILESIZE'
		 ||$key=='ZC_DISPLAY_COUNT'
		 ||$key=='ZC_SEARCH_COUNT'
		 ||$key=='ZC_PAGEBAR_COUNT'
		 ||$key=='ZC_COMMENTS_DISPLAY_COUNT'
		 ||$key=='ZC_MANAGE_COUNT'
		){
			$zbp->option[$key]=(integer)$value;
			continue;
		}		
		$zbp->option[$key]=trim(str_replace(array("\r","\n"),array("",""),$value));
	}
	$zbp->SaveOption();
}









################################################################################################################
function FilterMeta(&$object){

	//$type=strtolower(get_class($object));

	foreach ($_POST as $key => $value) {
		if(substr($key,0,5)=='meta_'){
			$name=substr($key,5-strlen($key));
			$object->Metas->$name=$value;
		}
	}

	foreach ($object->Metas->Data as $key => $value) {
		if($value=="")unset($object->Metas->Data[$key]);
	}

}


function FilterComment(&$comment){
	global $zbp;

	if(!CheckRegExp($comment->Name,'[username]')){
		$zbp->ShowError(15);
	}
	if($comment->Email && (!CheckRegExp($comment->Email,'[email]'))){
		$zbp->ShowError(29);
	}
	if($comment->HomePage && (!CheckRegExp($comment->HomePage,'[homepage]'))){
		$zbp->ShowError(30);
	}

	$comment->Name=substr($comment->Name, 0,20);
	$comment->Email=substr($comment->Email, 0,30);
	$comment->HomePage=substr($comment->HomePage, 0,100);

	$comment->Content=TransferHTML($comment->Content,'[nohtml]');

	$comment->Content=substr($comment->Content, 0,1000);
	$comment->Content=trim($comment->Content);
	if(strlen($comment->Content)==0){
		$zbp->ShowError(46);
	}
}


function FilterArticle(&$article){
	global $zbp;

	$article->Title=strip_tags($article->Title);
	$article->Alias=TransferHTML($article->Alias,'[normalname]');	

	if($article->Type == ZC_POST_TYPE_ARTICLE){
		if(!$zbp->CheckRights('ArticleAll')){
			$article->Content=TransferHTML($article->Content,'[noscript]');
			$article->Intro=TransferHTML($article->Intro,'[noscript]');
		}
	}elseif($article->Type == ZC_POST_TYPE_PAGE){
		if(!$zbp->CheckRights('PageAll')){
			$article->Content=TransferHTML($article->Content,'[noscript]');
			$article->Intro=TransferHTML($article->Intro,'[noscript]');
		}
	}
}


function FilterMember(&$member){
	global $zbp;
	$member->Intro=TransferHTML($member->Intro,'[noscript]');
	$member->Alias=TransferHTML($member->Alias,'[normalname]');	

	if(strlen($member->Name)<$zbp->option['ZC_USERNAME_MIN']||strlen($member->Name)>$zbp->option['ZC_USERNAME_MAX']){
		$zbp->ShowError(77);
	}

	if(!CheckRegExp($member->Name,'[username]')){
		$zbp->ShowError(77);
	}

	if(!CheckRegExp($member->Email,'[email]')){
		$member->Email='';
	}

	if(substr($member->HomePage,0,4)!='http'){
		$member->HomePage='http://' . $member->HomePage;
	}

	if(!CheckRegExp($member->HomePage,'[homepage]')){
		$member->HomePage='';
	}

	if(strlen($member->Email)>$zbp->option['ZC_EMAIL_MAX']){
		$zbp->ShowError(29);
	}

	if(strlen($member->HomePage)>$zbp->option['ZC_HOMEPAGE_MAX']){
		$zbp->ShowError(30);
	}

}


function FilterModule(&$module){
	global $zbp;
	$module->FileName=TransferHTML($module->FileName,'[filename]');
	$module->HtmlID=TransferHTML($module->HtmlID,'[normalname]');	
}



function FilterCategory(&$category){
	global $zbp;
	$category->Name=strip_tags($category->Name);
	$category->Alias=TransferHTML($category->Alias,'[normalname]');	
}


function FilterTag(&$tag){
	global $zbp;
	$tag->Name=strip_tags($tag->Name);
	$tag->Alias=TransferHTML($tag->Alias,'[normalname]');	
}


################################################################################################################
#统计函数
function CountNormalArticleNums(){
	global $zbp;
	$s=$zbp->db->sql->Count($zbp->table['Post'],array(array('COUNT','*','num')),array(array('=','log_Type',0),array('=','log_IsTop',0),array('=','log_Status',0)));
	$num=GetValueInArray(current($zbp->db->Query($s)),'num');

	$zbp->cache->normal_article_nums=$num;
	$zbp->SaveCache();
}

function CountPost(&$article){
	global $zbp;

	$id=$article->ID;

	$s=$zbp->db->sql->Count($zbp->table['Comment'],array(array('COUNT','*','num')),array(array('=','comm_LogID',$id),array('=','comm_IsChecking',0)));
	$num=GetValueInArray(current($zbp->db->Query($s)),'num');

	$article->CommNums=$num;
}

function CountPostArray($array){
	global $zbp;
	$array=array_unique($array);
	foreach ($array as $value) {
		if($value==0)continue;
		$article=new Post;
		$article->LoadInfoByID($value);
		CountPost($article);
		$article->Save();
	}
}

function CountCategory(&$category){
	global $zbp;

	$id=$category->ID;

	$s=$zbp->db->sql->Count($zbp->table['Post'],array(array('COUNT','*','num')),array(array('=','log_Type',0),array('=','log_IsTop',0),array('=','log_Status',0),array('=','log_CateID',$id)));
	$num=GetValueInArray(current($zbp->db->Query($s)),'num');

	$category->Count=$num;
}

function CountCategoryArray($array){
	global $zbp;
	$array=array_unique($array);
	foreach ($array as $value) {
		if($value==0)continue;
		CountCategory($zbp->categorys[$value]);
		$zbp->categorys[$value]->Save();
	}
}

function CountTag(&$tag){
	global $zbp;

	$id=$tag->ID;

	$s=$zbp->db->sql->Count($zbp->table['Post'],array(array('COUNT','*','num')),array(array('LIKE','log_Tag','%{'.$id.'}%')));
	$num=GetValueInArray(current($zbp->db->Query($s)),'num');

	$tag->Count=$num;
}

function CountTagArrayString($string){
	global $zbp;
	$array=$zbp->LoadTagsByIDString($string);
	foreach ($array as &$tag) {
		CountTag($tag);
		$tag->Save();
	}	
}

function CountMember(&$member){
	global $zbp;

	$id=$member->ID;

	$s=$zbp->db->sql->Count($zbp->table['Post'],array(array('COUNT','*','num')),array(array('=','log_Type',0),array('=','log_IsTop',0),array('=','log_Status',0),array('=','log_AuthorID',$id)));
	$member_Articles=GetValueInArray(current($zbp->db->Query($s)),'num');

	$s=$zbp->db->sql->Count($zbp->table['Post'],array(array('COUNT','*','num')),array(array('=','log_Type',0),array('=','log_IsTop',0),array('=','log_Status',0),array('=','log_AuthorID',$id)));
	$member_Pages=GetValueInArray(current($zbp->db->Query($s)),'num');

	$s=$zbp->db->sql->Count($zbp->table['Comment'],array(array('COUNT','*','num')),array(array('=','comm_AuthorID',$id)));
	$member_Comments=GetValueInArray(current($zbp->db->Query($s)),'num');

	$s=$zbp->db->sql->Count($zbp->table['Upload'],array(array('COUNT','*','num')),array(array('=','ul_AuthorID',$id)));
	$member_Uploads=GetValueInArray(current($zbp->db->Query($s)),'num');

	$member->Articles=$member_Articles;
	$member->Pages=$member_Pages;
	$member->Comments=$member_Comments;
	$member->Uploads=$member_Uploads;
}

function CountMemberArray($array){
	global $zbp;
	$array=array_unique($array);
	foreach ($array as $value) {
		if($value==0)continue;
		CountMember($zbp->members[$value]);
		$zbp->members[$value]->Save();
	}	
}







################################################################################################################
#BuildModule 
function BuildModule_catalog(){
	global $zbp;
	$s='';
	foreach ($zbp->categorysbyorder as $key => $value) {
		$s .='<li><a href="'.$value->Url.'">' . $value->Name . '</a></li>';
	}

	return $s;
}

function BuildModule_calendar($date=''){
	global $zbp;

	if($date=='')$date=date('Y-m',time());

	$s='<table id="tbCalendar"><caption>';

	$url=new UrlRule($zbp->option['ZC_DATE_REGEX']);
	$value=strtotime('-1 month',strtotime($date));
	$url->Rules['{%date%}']=date('Y-n',$value);
	$url->Rules['{%year%}']=date('Y',$value);
	$url->Rules['{%month%}']=date('n',$value);

	$url->Rules['{%day%}']=1;
	$s.='<a href="'.$url->Make().'">«</a>';

	$value=strtotime($date);
	$url->Rules['{%date%}']=date('Y-n',$value);
	$url->Rules['{%year%}']=date('Y',$value);
	$url->Rules['{%month%}']=date('n',$value);
	$s.='&nbsp;&nbsp;&nbsp;<a href="'.$url->Make().'">'.
		str_replace(array('%y%','%m%'), array(date('Y',$value),date('n',$value)), $zbp->lang['msg']['year_month'])
		.'</a>&nbsp;&nbsp;&nbsp;';

	$value=strtotime('+1 month',strtotime($date));
	$url->Rules['{%date%}']=date('Y-n',$value);
	$url->Rules['{%year%}']=date('Y',$value);
	$url->Rules['{%month%}']=date('n',$value);
	$s.='<a href="'.$url->Make().'">»</a></caption>';

	$s.='<thead><tr>';
	for ($i=1; $i < 8; $i++) { 
		$s.='<th title="'.$zbp->lang['week'][$i].'" scope="col"><small>'.$zbp->lang['week_abbr'][$i].'</small></th>';
	}

	$s.='</tr></thead>';
	$s.='<tbody>';
	$s.='<tr>';

	$a=1;
	$b=date('t',strtotime($date));
	$j=date('N',strtotime($date.'-1'));
	$k=7-date('N',strtotime($date.'-'.date('t',strtotime($date))));

	if($j>1){
		$s.='<td class="pad" colspan="'.($j-1).'"> </td>';
	}elseif($j=1){
		$s.='';
	}

	$l=$j-1;
	for ($i=$a; $i < $b+1; $i++) { 
		$s.='<td>'.$i.'</td>';

		$l=$l+1;
		if($l % 7==0)$s.='</tr><tr>';
	}

	if($k>1){
		$s.='<td class="pad" colspan="'.($k).'"> </td>';
	}elseif($k=1){
		$s.='';
	}

	$s.='</tr></tbody>';
	$s.='</table>';
	$s=str_replace('<tr></tr>', '', $s);

	$fdate = strtotime($date);
	$ldate = (strtotime(date('Y-m-t',strtotime($date)))+60*60*24);
	$sql = $zbp->db->sql->Select(
		$zbp->table['Post'],
		array('log_ID','log_PostTime'),
		array(array('=','log_Type','0'),array('=','log_Status','0'),array('BETWEEN','log_PostTime',$fdate,$ldate)),
		array('log_PostTime'=>'ASC'),
		null,
		null
		);
	$array=$zbp->db->Query($sql);
	$arraydate=array();
	$arrayid=array();
	foreach ($array as $key => $value) {
		$arraydate[date('j',$value['log_PostTime'])]=$value['log_ID'];
	}
	if(count($arraydate)>0){
		foreach ($arraydate as $key => $value) {
			$arrayid[]=array('log_ID',$value);
		}
		$articles=$zbp->GetArticleList('',array(array('array',$arrayid)),'','','');
		foreach ($arraydate as $key => $value) {
			$a=$zbp->GetPostByID($value);
			$s=str_replace('<td>'.$key.'</td>', '<td><a href="'.$a->Url.'">'.$key.'</a></td>', $s);
		}
	}

	return $s;

}

function BuildModule_comments(){
	global $zbp;

	$comments=$zbp->GetCommentList(
		array('*'),
		array(array('=','comm_IsChecking',0)),
		array('comm_PostTime'=>'DESC'),
		array(10),
		null
	);

	$s='';
	foreach ($comments as $comment) {
		$s .='<li><a href="'.$comment->Post->Url.'#cmt'.$comment->ID.'">' . TransferHTML($comment->Content,'[noenter]') . '</a></li>';
	}
	return $s;
}

function BuildModule_previous(){
	global $zbp;

	$articles=$zbp->GetArticleList(
		array('*'),
		array(array('=','log_Type',0),array('=','log_Status',0)),
		array('log_PostTime'=>'DESC'),
		array(10),
		null
	);
	$s='';
	foreach ($articles as $article) {
		$s .='<li><a href="'.$article->Url.'">' . $article->Title . '</a></li>';
	}
	return $s;
}

function BuildModule_archives(){
	global $zbp;

	$fdate;
	$ldate;

	$sql = $zbp->db->sql->Select($zbp->table['Post'],array('log_PostTime'),null,array('log_PostTime'=>'DESC'),array(1),null);

	$array=$zbp->db->Query($sql);

	if(count($array)==0)return '';

	$ldate=array(date('Y',$array[0]['log_PostTime']),date('m',$array[0]['log_PostTime']));


	$sql = $zbp->db->sql->Select($zbp->table['Post'],array('log_PostTime'),null,array('log_PostTime'=>'ASC'),array(1),null);

	$array=$zbp->db->Query($sql);

	if(count($array)==0)return '';

	$fdate=array(date('Y',$array[0]['log_PostTime']),date('m',$array[0]['log_PostTime']));

	$arraydate=array();

	for ($i=$fdate[0]; $i < $ldate[0]+1; $i++) { 
		for ($j=1; $j<13 ; $j++) { 
			$arraydate[]=strtotime($i . '-' . $j);
		}
	}

	foreach ($arraydate as $key => $value) {
		if( $value - strtotime($ldate[0] . '-' . $ldate[1]) >0)unset($arraydate[$key]);
		if( $value - strtotime($fdate[0] . '-' . $fdate[1]) <0)unset($arraydate[$key]);
	}

	$arraydate=array_reverse($arraydate);

	$s='';

	foreach ($arraydate as $key => $value) {
		$url=new UrlRule($zbp->option['ZC_DATE_REGEX']);
		$url->Rules['{%date%}']=date('Y-n',$value);
		$url->Rules['{%year%}']=date('Y',$value);
		$url->Rules['{%month%}']=date('n',$value);
		$url->Rules['{%day%}']=1;

		$fdate = $value;
		$ldate = (strtotime(date('Y-m-t',$value))+60*60*24);
		$sql = $zbp->db->sql->Count($zbp->table['Post'],array(array('COUNT','*','num')),array(array('=','log_Type','0'),array('=','log_Status','0'),array('BETWEEN','log_PostTime',$fdate,$ldate)));
		$n=GetValueInArray(current($zbp->db->Query($sql)),'num');
		if($n>0){
			$s.='<li><a href="'.$url->Make().'">'.
				str_replace(array('%y%','%m%'), array(date('Y',$fdate),date('n',$fdate)), $zbp->lang['msg']['year_month'])
				.' (' . $n  . ')</a></li>';
		}
	}

	return $s;

}

?>