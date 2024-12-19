<?php
class Lib_ArticleSEO
{
  private $domain = 'www.example.com';

  public function __construct() {
  }

  public function result($params) {
    $content          = $params['content'];
    $content_full     = $params['content_full'];
    $title            = $params['title'];
    $keyphrase        = $params['keyphrase'];
    $list_keyphrase   = $params['list_keyphrase'];
    $slug             = $params['slug'];
    $meta_description = $params['meta_description'];

    $score = $this->fleschKincaidGrade($content);
    return [
      'readability' => [
        'score'           => round($score, 2),                                                        //* Score
        'score_ket'       => $this->readabilityDescription('flesch_kincaid', $score)['keterangan'],   //* Score - Keterangan
        'score_info'      => $this->readabilityDescription('flesch_kincaid', $score)['informasi'],    //* Score - Informasi
        'word_count'      => $this->wordCount($content),                                              //* Word count
        'reading_time'    => $this->readingTime($content),                                            //* Reading time
        'keyword_list'    => $this->extractKeywords($content, $title)                                 //* Keyword list
      ],
      'insight'     => [
        'outbound_links'  => $this->analyzeOutboundLinks($content_full, $this->domain),               //* Outbound links
        'internal_links'  => $this->analyzeInternalLinks($content_full, $this->domain),               //* Internal links
        'images'          => $this->analyzeImages($content_full),                                     //* Images
        'text_length'     => $this->analyzeWordCount($content),                                       //* Text length
        'seo_title_width' => $this->analyzePageTitle($title),                                         //* SEO title width
      ],
      'keyphrase'   => [
        'introduction'    => $this->analyzeFocusKeyphraseInIntroduction($content, $keyphrase),        //* Keyphrase in introduction
        'density'         => $this->analyzeKeyphraseDensity($content, $keyphrase),                    //* Keyphrase density
        'distribution'    => $this->analyzeKeyphraseDistribution($content, $keyphrase),               //* Keyphrase distribution
        'length'          => $this->analyzeKeyphraseLength($keyphrase),                               //* Keyphrase length
        'subheadings'     => $this->analyzeKeyphraseInSubheadings($content_full, $keyphrase),         //* Keyphrase in subheadings
        'images'          => $this->analyzeImageAltTags($content_full, $keyphrase),                   //* Keyphrase in Image alt tags
        'title'           => $this->analyzeKeyphraseInTitle($title, $keyphrase),                      //* Keyphrase in page title
        'link'            => $this->analyzeLinkKeyphrase($content_full, $keyphrase),                  //* Keyphrase in link
        'used'            => $this->analyzePreviouslyUsedKeyphrase($keyphrase, $list_keyphrase),      //* Previously used keyphrase
        'slug'            => $this->analyzeKeyphraseInSlug($keyphrase, $slug),                        //* Keyphrase in slug
        'meta_desc'       => $this->analyzeKeyphraseInMetaDescription($meta_description, $keyphrase), //* Keyphrase in Meta Description
        'meta_desc_length'=> $this->analyzeMetaDescriptionLength($meta_description)                   //* Meta Description length
      ]
    ];
  }

  //! KEYPHRASE

  public function analyzeFocusKeyphraseInIntroduction($content, $keyphrase) {
    //* Extract the first 150 characters or first sentence as the introduction
    $introduction         = substr($content, 0, strpos($content, '.') + 1) ?: substr($content, 0, 150);
    $is_keyphrase_present = stripos($introduction, $keyphrase) !== false;

    $result = [
      'introduction'      => $introduction,
      'keyphrase_present' => $is_keyphrase_present,
      'status'            => '',
      'message'           => '',
    ];

    if ($is_keyphrase_present) {
      $result['status']  = 'green';
      $result['message'] = 'Keyphrase ditemukan di paragraf pembuka. Ini bagus untuk SEO.';
    } else {
      $result['status']  = 'red';
      $result['message'] = 'Keyphrase tidak ditemukan di paragraf pembuka. Tambahkan untuk meningkatkan relevansi SEO.';
    }

    return $result;
  }

  public function analyzeKeyphraseDensity($content, $keyphrase) {
    $clean_content   = strip_tags($content);        // Remove HTML tags
    $clean_content   = strtolower($clean_content);  // Normalize case
    $clean_keyphrase = strtolower($keyphrase);      // Normalize case

    // Count occurrences of the keyphrase
    $keyphrase_count = substr_count($clean_content, $clean_keyphrase);

    // Count total words in the content
    $total_words = str_word_count($clean_content);

    // Calculate keyphrase density
    $density = $total_words > 0 ? ($keyphrase_count / $total_words) * 100 : 0;

    $result = [
      'keyphrase_count' => $keyphrase_count,
      'total_words'     => $total_words,
      'density'         => round($density, 2),
      'status'          => '',
      'message'         => '',
    ];

    if ($keyphrase_count === 0) {
      $result['status']  = 'red';
      $result['message'] = 'Keyphrase tidak ditemukan dalam konten.';
    } elseif ($density > 2.5) {
      $result['status']  = 'orange';
      $result['message'] = 'Kepadatan keyphrase terlalu tinggi. Kurangi penggunaan keyphrase.';
    } elseif ($density >= 0.5) {
      $result['status']  = 'green';
      $result['message'] = "Kepadatan keyphrase: Keyphrase ditemukan {$keyphrase_count} kali. Ini bagus!";
    } else {
      $result['status']  = 'orange';
      $result['message'] = 'Kepadatan keyphrase terlalu rendah. Tambahkan lebih banyak keyphrase untuk meningkatkan relevansi.';
    }

    return $result;
  }

  public function analyzeKeyphraseDistribution($content, $keyphrase){
    // Clean and normalize the content
    $clean_content = strtolower(strip_tags($content)); // Remove HTML and normalize case
    $clean_keyphrase = strtolower($keyphrase); // Normalize case

    // Split content into three sections: beginning, middle, and end
    $total_length = strlen($clean_content);
    $third_length = ceil($total_length / 3);

    $sections = [
      'beginning' => substr($clean_content, 0, $third_length),
      'middle'    => substr($clean_content, $third_length, $third_length),
      'end'       => substr($clean_content, $third_length * 2),
    ];

    // Count keyphrase occurrences in each section
    $keyphrase_counts = [
      'beginning' => substr_count($sections['beginning'], $clean_keyphrase),
      'middle'    => substr_count($sections['middle'], $clean_keyphrase),
      'end'       => substr_count($sections['end'], $clean_keyphrase),
    ];

    $result = [
      'keyphrase_distribution' => $keyphrase_counts,
      'status'                => '',
      'message'               => '',
    ];

    // Check if the keyphrase is evenly distributed
    $non_empty_sections = array_filter($keyphrase_counts, fn($count) => $count > 0);

    if (count($non_empty_sections) === 3) {
      $result['status']  = 'green';
      $result['message'] = 'Bagus sekali! Keyphrase terdistribusi secara merata di seluruh konten.';
    } elseif (count($non_empty_sections) === 2) {
      $result['status']  = 'orange';
      $result['message'] = 'Keyphrase ditemukan di dua bagian. Pertimbangkan untuk mendistribusikannya lebih merata.';
    } elseif (count($non_empty_sections) === 1) {
      $result['status']  = 'red';
      $result['message'] = 'Keyphrase hanya ditemukan di satu bagian. Sebarkan lebih merata di seluruh konten.';
    } else {
      $result['status']  = 'red';
      $result['message'] = 'Keyphrase tidak ditemukan. Tambahkan ke dalam konten untuk meningkatkan SEO.';
    }

    return $result;
  }

  public function analyzeKeyphraseLength($keyphrase) {
    $keyphrase_length = str_word_count($keyphrase);
    
    $result = [
        'keyphrase' => $keyphrase,
        'length'    => $keyphrase_length,
        'status'    => '',
        'message'   => '',
    ];

    if ($keyphrase_length >= 2 && $keyphrase_length <= 4) {
      $result['status']  = 'green';
      $result['message'] = 'Bagus sekali! Panjang keyphrase ideal untuk SEO.';
    } elseif ($keyphrase_length < 2) {
      $result['status']  = 'red';
      $result['message'] = 'Keyphrase terlalu pendek. Pertimbangkan menambahkan kata untuk meningkatkan relevansi.';
    } elseif ($keyphrase_length > 4) {
      $result['status']  = 'orange';
      $result['message'] = 'Keyphrase terlalu panjang. Usahakan antara 2–4 kata untuk hasil optimal.';
    }

    return $result;
  }

  public function analyzeKeyphraseInSubheadings($content, $keyphrase) {
    // Extract all <h2> and <h3> subheadings from the content
    preg_match_all('/<h2[^>]*>(.*?)<\/h2>|<h3[^>]*>(.*?)<\/h3>/i', $content, $matches);
    $subheadings = array_filter(array_merge($matches[1], $matches[2]));

    $keyphrase_count = 0;
    foreach ($subheadings as $subheading) {
      if (stripos($subheading, $keyphrase) !== false) {
        $keyphrase_count++;
      }
    }

    $total_subheadings = count($subheadings);

    $result = [
      'keyphrase'          => $keyphrase,
      'total_subheadings'  => $total_subheadings,
      'keyphrase_count'    => $keyphrase_count,
      'status'             => '',
      'message'            => '',
    ];

    if ($keyphrase_count >= 2) {
      $result['status']  = 'green';
      $result['message'] = "{$keyphrase_count} dari subheading H2 dan H3 mencerminkan topik konten Anda. Kerja bagus!";
    } elseif ($keyphrase_count === 1) {
      $result['status']  = 'orange';
      $result['message'] = "Hanya 1 subheading H2 atau H3 yang mencerminkan topik konten Anda. Pertimbangkan menambahkannya ke lebih banyak subheading.";
    } else {
      $result['status']  = 'red';
      $result['message'] = "Tidak ada subheading H2 atau H3 yang mencerminkan topik konten Anda. Tambahkan keyphrase ke subheading untuk meningkatkan SEO.";
    }

    return $result;
  }

  public function analyzeImageAltTags($content, $keyphrase) {
    // Extract all <img> tags from the content
    preg_match_all('/<img[^>]+>/i', $content, $imageTags);
    $images = $imageTags[0];

    // Break keyphrase into words
    $keyphrase_words = array_filter(explode(' ', strtolower($keyphrase)));

    $total_images          = count($images);
    $images_with_alt       = 0;
    $images_with_keyphrase = 0;

    foreach ($images as $image) {
      // Extract the alt attribute
      preg_match('/alt="([^"]*)"/i', $image, $altMatch);
      $altText = isset($altMatch[1]) ? strtolower($altMatch[1]) : '';

      if (!empty($altText)) {
        $images_with_alt++;
        // Count the number of keyphrase words in the alt text
        $keyphrase_word_count = 0;
        foreach ($keyphrase_words as $word) {
          if (stripos($altText, $word) !== false) {
            $keyphrase_word_count++;
          }
        }

        // Check if at least half of the keyphrase words are present in the alt text
        if ($keyphrase_word_count >= ceil(count($keyphrase_words) / 2)) {
          $images_with_keyphrase++;
        }
      }
    }

    $result = [
        'keyphrase'             => $keyphrase,
        'total_images'          => $total_images,
        'images_with_alt'       => $images_with_alt,
        'images_with_keyphrase' => $images_with_keyphrase,
        'status'                => '',
        'message'               => '',
    ];

    if ($total_images === 0) {
      $result['status']  = 'red';
      $result['message'] = 'Tidak ada gambar pada halaman ini. Tambahkan gambar yang relevan untuk meningkatkan SEO.';
    } elseif ($images_with_keyphrase >= ceil($total_images / 2)) {
      $result['status']  = 'green';
      $result['message'] = "Gambar di halaman ini memiliki alt attribute dengan setidaknya setengah kata dari keyphrase Anda. Kerja bagus!";
    } elseif ($images_with_keyphrase > 0) {
      $result['status']  = 'orange';
      $result['message'] = "Beberapa gambar memiliki alt attribute yang mengandung keyphrase Anda. Pertimbangkan menambahkan alt attribute pada lebih banyak gambar.";
    } else {
      $result['status']  = 'red';
      $result['message'] = "Tidak ada gambar di halaman ini yang memiliki alt attribute dengan keyphrase Anda. Perbaiki untuk meningkatkan SEO.";
    }

    return $result;
  }

  public function analyzeKeyphraseInTitle($title, $keyphrase) {
    // Clean and normalize the inputs
    $clean_title = strtolower($title); // Normalize case for title
    $clean_keyphrase = strtolower($keyphrase); // Normalize case for keyphrase

    $result = [
      'title'     => $title,
      'keyphrase' => $keyphrase,
      'status'    => '',
      'message'   => '',
    ];

    // Check if the keyphrase is in the title
    if (strpos($clean_title, $clean_keyphrase) !== false) {
      // Check if the keyphrase is at the beginning of the title
      if (strpos($clean_title, $clean_keyphrase) === 0) {
        // Keyphrase is at the beginning
        $result['status'] = 'green';
        $result['message'] = 'Keyphrase berada di awal judul. Ini sangat baik untuk SEO.';
      } else {
        // Keyphrase is not at the beginning
        $result['status'] = 'orange';
        $result['message'] = 'Keyphrase ada di judul, tetapi tidak di awal. Pindahkan ke awal untuk hasil terbaik.';
      }
    } else {
      // Keyphrase is not found in the title
      $result['status'] = 'red';
      $result['message'] = 'Keyphrase tidak ditemukan dalam judul. Tambahkan keyphrase untuk meningkatkan relevansi SEO.';
    }

    return $result;
  }

  public function analyzeLinkKeyphrase($content, $keyphrase) {
    // Clean and normalize the content
    $clean_content = strtolower(strip_tags($content)); // Remove HTML tags and normalize case
    $clean_keyphrase = strtolower($keyphrase); // Normalize keyphrase case

    // Search for links in the content and their anchor text
    preg_match_all('/<a[^>]*>(.*?)<\/a>/', $content, $matches); // Find all anchor tags and their text

    $link_keyphrase_count = 0;

    // Loop through the matches and check if the keyphrase is in the anchor text
    foreach ($matches[1] as $anchor_text) {
      if (strpos(strtolower($anchor_text),$clean_keyphrase) !== false) {
        $link_keyphrase_count++;
      }
    }

    $result = [
      'link_keyphrase_count' => $link_keyphrase_count,
      'status'               => '',
      'message'              => '',
    ];

    // Check if the keyphrase is used in any anchor text
    if ($link_keyphrase_count > 0) {
      // Keyphrase is found in anchor text
      $result['status'] = 'red';
      $result['message'] = 'Link keyphrase: Anda menautkan ke halaman lain dengan kata-kata yang ingin Anda rangkingkan. Jangan lakukan itu!';
    } else {
      // Keyphrase is not found in anchor text
      $result['status'] = 'green';
      $result['message'] = 'Link keyphrase: Tidak ada penggunaan kata kunci dalam teks anchor. Ini bagus untuk SEO.';
    }

    return $result;
  }

  public function analyzePreviouslyUsedKeyphrase($keyphrase, $used_keyphrases) {
    $result = [
      'keyphrase'       => $keyphrase,
      'previously_used' => false,
      'status'          => '',
      'message'         => '',
    ];

    // Validate if $used_keyphrases is an array and not empty
    if (!is_array($used_keyphrases) || empty($used_keyphrases)) {
      $result['status']  = 'green';
      $result['message'] = 'Bagus! Tidak ada riwayat keyphrase sebelumnya.';
      return $result;
    }

    // Normalize the keyphrase and the list of used keyphrases
    $clean_keyphrase = strtolower(trim($keyphrase));
    $clean_used_keyphrases = array_map('strtolower', array_map('trim', $used_keyphrases));

    // Check if the keyphrase is in the list of used keyphrases
    if (in_array($clean_keyphrase, $clean_used_keyphrases, true)) {
      $result['previously_used'] = true;
      $result['status']          = 'red';
      $result['message']         = 'Keyphrase sebelumnya telah digunakan. Jangan gunakan keyphrase lebih dari sekali.';
    } else {
      $result['status']  = 'green';
      $result['message'] = 'Bagus! Keyphrase ini belum pernah digunakan sebelumnya.';
    }

    return $result;
  }

  public function analyzeKeyphraseInSlug($keyphrase, $slug) {
    // Validate if $slug is not empty
    if (empty($slug)) {
      $result['status']  = 'red';
      $result['message'] = 'Slug tidak boleh kosong. Tambahkan slug untuk meningkatkan SEO.';
      return $result;
    }

    $result = [
      'keyphrase'         => $keyphrase,
      'slug'              => $slug,
      'keyphrase_in_slug' => false,
      'status'            => '',
      'message'           => '',
    ];

    // Normalize the keyphrase and slug
    $clean_keyphrase = strtolower(trim($keyphrase));
    $clean_slug = strtolower(trim($slug));

    // Check if the keyphrase exists in the slug
    if (strpos($clean_slug, $clean_keyphrase) !== false) {
      $result['keyphrase_in_slug'] = true;
      $result['status']  = 'green';
      $result['message'] = 'Keyphrase dalam slug: Kerja bagus!';
    } else {
      $result['status']  = 'red';
      $result['message'] = 'Keyphrase tidak ditemukan dalam slug. Tambahkan untuk meningkatkan SEO.';
    }

    return $result;
  }

  public function analyzeKeyphraseInMetaDescription($meta_description, $keyphrase) {
    // Validate if $meta_description is not empty
    if (empty($meta_description)) {
      $result['status']  = 'red';
      $result['message'] = 'Meta description kosong. Tambahkan untuk meningkatkan relevansi SEO.';
      return $result;
    }

    // Normalize the meta description and keyphrase
    $clean_description = strtolower(strip_tags($meta_description)); // Remove HTML and normalize case
    $clean_keyphrase   = strtolower($keyphrase); // Normalize case

    // Check if the keyphrase is present in the meta description
    $is_keyphrase_present = stripos($clean_description, $clean_keyphrase) !== false;

    $result = [
      'meta_description'   => $meta_description,
      'keyphrase_present'  => $is_keyphrase_present,
      'status'             => '',
      'message'            => '',
    ];

    // Provide feedback based on the presence of the keyphrase
    if ($is_keyphrase_present) {
      $result['status']  = 'green';
      $result['message'] = 'Keyphrase muncul dalam meta description. Bagus sekali!';
    } else {
      $result['status']  = 'red';
      $result['message'] = 'Keyphrase tidak ditemukan dalam meta description. Tambahkan untuk meningkatkan relevansi SEO.';
    }

    return $result;
  }

  public function analyzeMetaDescriptionLength($meta_description) {
    // Calculate the length of the meta description
    $description_length = strlen($meta_description);

    $result = [
      'meta_description' => $meta_description,
      'length'           => $description_length,
      'status'           => '',
      'message'          => '',
    ];

    // Validate if $meta_description is not empty
    if (empty($meta_description)) {
      $result['status']  = 'red';
      $result['message'] = 'Meta description kosong. Tambahkan untuk meningkatkan relevansi SEO.';
      return $result;
    }

    // Analyze the length and provide feedback
    if ($description_length > 156) {
      $result['status']  = 'red';
      $result['message'] = 'Panjang meta description lebih dari 156 karakter. Kurangi panjangnya agar seluruh deskripsi terlihat.';
    } elseif ($description_length >= 120 && $description_length <= 156) {
      $result['status']  = 'green';
      $result['message'] = 'Panjang meta description sangat baik! Pastikan tetap relevan dengan konten.';
    } elseif ($description_length < 120 && $description_length > 0) {
      $result['status']  = 'orange';
      $result['message'] = 'Panjang meta description kurang dari ideal. Tambahkan beberapa kata lagi untuk menjelaskan konten lebih baik.';
    } else {
      $result['status']  = 'red';
      $result['message'] = 'Meta description tidak ditemukan. Tambahkan deskripsi untuk meningkatkan SEO.';
    }

    return $result;
  }

  //! READABILITY
  /**
   *
   * SOURCE:
   * https://readable.com/readability/flesch-reading-ease-flesch-kincaid-grade-level/
   *
   **/

  public function wordCount($content) {
    $word_count = str_word_count(strip_tags($content));
    return $word_count;
  }

  public function readingTime($content) {
    $word_count = str_word_count(strip_tags($content));
    $words_per_minute = 200; //* Average reading speed
    $time_in_minutes = ceil($word_count / $words_per_minute); //* Pembulatan
    return $time_in_minutes;
  }

  public function extractKeywords($content, $title) {
    $all_text = strtolower($content . ' ' . $title);
    $words = array_count_values(str_word_count($all_text, 1));
    arsort($words);
    return array_slice($words, 0, 10, true);
  }

  //* Flesch-Kincaid Grade Level lebih fokus pada tingkat pendidikan atau kelas sekolah yang dibutuhkan untuk memahami teks.
  public function fleschKincaidGrade($text) {
    $words     = str_word_count($text, 1);
    $sentences = preg_split('/[.!?]/', $text);
    $syllables = 0;
    foreach ($words as $word) {
      $syllables += $this->syllableCount($word);
    }
    $word_count     = count($words);
    $sentence_count = count($sentences);
    return 0.39 * ($word_count / $sentence_count) + 11.8 * ($syllables / $word_count) - 15.59;
  }

  //* Flesch Reading Ease memberikan angka yang lebih intuitif (semakin tinggi angkanya, semakin mudah dibaca).
  public function fleschReadingEase($text) {
    $words     = str_word_count($text, 1);
    $sentences = preg_split('/[.!?]/', $text);
    $syllables = 0;
    foreach ($words as $word) {
      $syllables += $this->syllableCount($word);
    }
    $word_count     = count($words);
    $sentence_count = max(1, count($sentences));
    return 206.835 - (1.015 * ($word_count / $sentence_count)) - (84.6 * ($syllables / $word_count));
  }

  private function syllableCount($word) {
    return max(1, preg_match_all('/[aeiouy]+/', strtolower($word)));
  }

  private function readabilityDescription($type, $score) {
    switch($type) {
      case 'flesch_reading_ease':
        if ($score >= 90) {
          return [
            'keterangan' => 'Sangat mudah dibaca',
            'informasi'  => 'Cocok untuk anak-anak atau teks sederhana'
          ];
        } elseif ($score >= 60) {
          return [
            'keterangan' => 'Cukup mudah dibaca',
            'informasi'  => 'Cocok untuk siswa SMA atau pembaca umum'
          ];
        } elseif ($score >= 30) {
          return [
            'keterangan' => 'Sulit dibaca',
            'informasi'  => 'Cocok untuk pembaca dewasa atau profesional'
          ];
        } else {
          return [
            'keterangan' => 'Sangat sulit dibaca',
            'informasi'  => 'Cocok untuk akademik / teknis'
          ];
        }
        break;
      case 'flesch_kincaid':
        if ($score <= 5) {
          return [
            'keterangan' => 'Sangat mudah dibaca',
            'informasi'  => 'Cocok untuk anak-anak kelas 5 atau lebih muda'
          ];
        } elseif ($score <= 8) {
          return [
            'keterangan' => 'Cukup mudah dibaca',
            'informasi'  => 'Cocok untuk siswa kelas 6 hingga 8'
          ];
        } elseif ($score <= 12) {
          return [
            'keterangan' => 'Sedang',
            'informasi'  => 'Cocok untuk siswa SMA'
          ];
        } elseif ($score <= 16) {
          return [
            'keterangan' => 'Sulit dibaca',
            'informasi'  => 'Cocok untuk mahasiswa tingkat awal'
          ];
        } else {
          return [
            'keterangan' => 'Sangat sulit dibaca',
            'informasi'  => 'Cocok untuk mahasiswa tingkat lanjut atau teks teknis'
          ];
        }
        break;
    }
  }

  //! INSIGHT

  public function analyzeOutboundLinks($htmlContent, $currentDomain) {
    $dom = new DOMDocument();
    @$dom->loadHTML($htmlContent);
    $links = $dom->getElementsByTagName('a');

    $result = [
        'total_outbound_links' => 0,
        'followed_links'       => 0,
        'nofollowed_links'     => 0,
        'sponsored_links'      => 0,
        'ugc_links'            => 0,
        'status'               => 'red',
        'info'                 => ''
    ];

    foreach ($links as $link) {
      $href = $link->getAttribute('href');
      $rel  = strtolower($link->getAttribute('rel'));

      if (
        !empty($href) &&
        strpos($href, $currentDomain) === false &&
        substr($href, 0, 1) !== '/'
      ) {
        $result['total_outbound_links']++;
        if (strpos($rel, 'nofollow') !== false) {
          $result['nofollowed_links']++;
        } elseif (strpos($rel, 'sponsored') !== false) {
          $result['sponsored_links']++;
        } elseif (strpos($rel, 'ugc') !== false) {
          $result['ugc_links']++;
        } else {
          $result['followed_links']++;
        }
      }
    }

    if ($result['followed_links'] > 0) {
      $result['status'] = 'green';
      $result['info']   = 'At least one followed link';
    } elseif ($result['nofollowed_links'] > 0) {
      $result['status'] = 'orange';
      $result['info']   = 'Only nofollowed links';
    } else {
      $result['status'] = 'red';
      $result['info']   = 'No outbound links';
    }

    return $result;
  }

  public function analyzeInternalLinks($content, $currentDomain) {
    $result = [
      'total_internal_links' => 0,
      'nofollowed_links'     => 0,
      'followed_links'       => 0,
      'status'               => 'red', // Default traffic light status
      'message'              => 'No internal links found', // Default message
    ];

    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'));
    libxml_clear_errors();

    $links = $dom->getElementsByTagName('a');
    foreach ($links as $link) {
      $href = $link->getAttribute('href');
      $rel  = $link->getAttribute('rel');

      if (
        !empty($href)
        && (strpos($href, $currentDomain) !== false
        || substr($href, 0, 1) === '/')
      ) {
        $result['total_internal_links']++;
        if (strpos($rel, 'nofollow') !== false) {
          $result['nofollowed_links']++;
        } else {
          $result['followed_links']++;
        }
      }
    }

    if ($result['total_internal_links'] > 0) {
      if ($result['followed_links'] > 0) {
        $result['status']  = 'green';
        $result['message'] = 'Internal link sudah dioptimalkan dengan link yang diikuti (followed).';
      } else {
        $result['status']  = 'orange';
        $result['message'] = 'Hanya ditemukan internal link nofollow. Pertimbangkan untuk menambahkan link yang diikuti (followed).';
      }
    } else {
      $result['status']  = 'red';
      $result['message'] = 'Tidak ada internal link ditemukan. Tambahkan internal link untuk meningkatkan SEO.';
    }

    return $result;
  }

  public function analyzeImages($content) {
    $result = [
      'total_images'       => 0,
      'images_with_alt'    => 0,
      'images_without_alt' => 0,
      'status'             => '',
      'message'            => '',
    ];

    preg_match_all('/<img[^>]+>/i', $content, $images);

    $result['total_images'] = count($images[0]);

    if ($result['total_images'] > 0) {
      foreach ($images[0] as $image) {
        if (preg_match('/alt=["\']([^"\']+)["\']/', $image)) {
          $result['images_with_alt']++;
        } else {
          $result['images_without_alt']++;
        }
      }

      if ($result['images_with_alt'] === $result['total_images']) {
        $result['status']  = 'green';
        $result['message'] = 'Semua gambar memiliki atribut alt yang baik.';
      } elseif ($result['images_with_alt'] > 0) {
        $result['status']  = 'orange';
        $result['message'] = 'Beberapa gambar tidak memiliki atribut alt. Tambahkan deskripsi alt untuk meningkatkan SEO.';
      } else {
        $result['status']  = 'red';
        $result['message'] = 'Tidak ada gambar yang memiliki atribut alt. Tambahkan deskripsi alt untuk semua gambar.';
      }
    } else {
      $result['status']  = 'red';
      $result['message'] = 'Tidak ada gambar ditemukan dalam konten. Tambahkan gambar untuk membuat konten lebih menarik.';
    }

    return $result;
  }

  public function analyzeWordCount($content) {
    $word_count = $this->wordCount($content);
    $result = [
      'word_count' => $word_count,
      'status'     => '',
      'message'    => '',
    ];

    if ($word_count >= 900) {
      $result['status']  = 'green';
      $result['message'] = 'Artikel memiliki jumlah kata yang sangat baik untuk SEO.';
    } elseif ($word_count >= 300) {
      $result['status']  = 'orange';
      $result['message'] = 'Jumlah kata cukup, tetapi lebih banyak kata akan meningkatkan kualitas SEO.';
    } else {
      $result['status']  = 'red';
      $result['message'] = 'Jumlah kata terlalu sedikit. Tambahkan lebih banyak konten untuk meningkatkan SEO.';
    }

    return $result;
  }

  public function analyzePageTitle($title) {
    $title_length = strlen($title);

    $result = [
        'title'   => $title,
        'length'  => $title_length,
        'status'  => '',
        'message' => '',
    ];

    if ($title_length < 50 || $title_length > 60) {
      $result['status']  = 'orange';
      $result['message'] = 'Panjang judul tidak ideal. Pastikan antara 50–60 karakter.';
    } elseif ($title_length >= 50 && $title_length <= 60) {
      $result['status']  = 'green';
      $result['message'] = 'Judul halaman sangat baik untuk SEO. Panjang ideal dan unik.';
    }

    return $result;
  }

}