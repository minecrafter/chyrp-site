<?php
	class Photo extends Feather {
		public function __construct() {
			$this->setFilter("caption", "markup_post_text");
			$this->respondTo("delete_post", "delete_file");
		}
		static function submit() {
			$filename = "";
			if (isset($_FILES['photo']) and $_FILES['photo']['error'] == 0) {
				$filename = upload($_FILES['photo'], array("jpg", "jpeg", "png", "gif", "tiff", "bmp"));
			} else {
				error(__("Error"), __("Couldn't upload photo."));
			}
			
			$yaml = Spyc::YAMLDump(array("filename" => $filename, "caption" => $_POST['caption']));
			$clean = (!empty($_POST['slug'])) ? $_POST['slug'] : "" ;
			$url = Post::check_url($clean);
			
			$post = Post::add($yaml, $clean, $url);
			
			# Send any and all pingbacks to URLs in the caption
			$config = Config::current();
			if ($config->send_pingbacks)
				send_pingbacks($_POST['caption'], $post->id);
			
			$route = Route::current();
			if (isset($_POST['bookmarklet']))
				$route->redirect($route->url("bookmarklet/done/"));
			else
				$route->redirect($post->url());
		}
		static function update() {
			$post = new Post($_POST['id']);
			
			if (isset($_FILES['photo']) and $_FILES['photo']['error'] == 0) {
				delete_photo_file($_POST['id']);
				$filename = upload($_FILES['photo']);
			} else {
				$filename = $post->filename;
			}
			
			$yaml = Spyc::YAMLDump(array("filename" => $filename, "caption" => $_POST['caption']));
			
			$post->update($yaml);
		}
		static function title($id) {
			$post = new Post($id);
			$caption = $post->title_from_excerpt();
			return fallback($caption, $post->filename, true);
		}
		static function excerpt($id) {
			$post = new Post($id);
			return $post->caption;
		}
		static function feed_content($id) {
			$post = new Post($id);
			return image_tag_for($post->filename, 500, 500)."<br /><br />".$post->caption;
		}
		static function delete_file($id) {
			$post = new Post($id);
			if ($post->feather != "photo") return;
			unlink(MAIN_DIR."/upload/".$post->filename);
		}
	}
	
	function image_tag_for($filename, $max_width = null, $max_height = null, $more_args = "q=100") {
		$ext = pathinfo($filename, PATHINFO_EXTENSION);
		$config = Config::current();
		return '<a href="'.$config->url.'/upload/'.$filename.'"><img src="'.$config->url.'/feathers/photo/lib/phpThumb.php?src='.$config->url.'/upload/'.urlencode($filename).'&amp;w='.$max_width.'&amp;h='.$max_height.'&amp;f='.$ext.'&amp;'.$more_args.'" alt="'.$filename.'" /></a>';
	}