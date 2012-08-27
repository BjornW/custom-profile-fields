<?php 
// initialize variables
$current_author = $current_author_id = null;

// retrieve the author's data
if( isset($_GET['author_name']) ) {
  $current_author = get_user_by ( 'slug', $author_name );
} else {
  $current_author = get_userdata( intval($author) );
}
// make life easier for themers by setting the author's  id
if( is_object($current_author) && property_exists($current_author, 'ID') ) {
  $current_author_id = $current_author->ID;
}

?>

<h1>Basic author template example</h1>
<?php if( ! is_null($current_author_id) ) : ?>
  <ul>
    <li>Display name: <?php the_author_meta('display_name', $current_author_id); ?></li>
    <li>Email address: <?php the_author_meta('email', $current_author_id); ?></li>
  </ul>
<?php else : ?>
  <p>Strange, no user id was found. Can't get profile data</p>
<?php endif; ?>


<h2>How does it work?</h2>
<p>Using the default WordPress <a href="http://codex.wordpress.org/Function_Reference/the_author_meta">the_author_meta()</a> and 
<a href="http://codex.wordpress.org/Function_Reference/get_the_author_meta">get_the_author_meta()</a> functions you will be able to show
an author's meta data. In the example (see the php source code) I use the default metadata fields 'display_name' and 'email', but you can use
any field created by the bbExtendProfile plugin using the field_meta_key instead of one of the default metadata field. You can find these field_meta_key
names using the bbExtendProfile plugin. In the main menu you will see a section labeled 'Available Fields'. In this section all your created fields
are shown. By using the field_meta_key in the get_the_author() function you are able to retrieve the value belonging to that field. <br /> E.g:</p>
<pre>
    <li>Display name: &lt;?php the_author_meta('display_name', $current_author_id); ?&gt;</li>
</pre>
<p> You can use (if you have created the field!)</p>
<pre>
    <li>Telephone: &lt;?php the_author_meta('telephone', $current_author_id); ?&gt;</li>
</pre>





