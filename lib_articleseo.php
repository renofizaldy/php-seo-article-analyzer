<?php
class Lib_ArticleSEO
{
  private $domain = 'www.example.com';

  public function __construct() {
  }

  public function result($params) {
    $content          = $params['content'];
    $title            = $params['title'];
    $keyphrase        = $params['keyphrase'];
    $list_keyphrase   = $params['list_keyphrase'];
    $slug             = $params['slug'];
    $meta_description = $params['meta_description'];

    $score = $this->fleschKincaidGrade($content);
    return [
      'insights'    => [
        'score'                         => round($score, 2),                                                          //* Score
        'score_ket'                     => $this->readabilityDescription('flesch_kincaid', $score)['keterangan'],     //* Score - Keterangan
        'score_info'                    => $this->readabilityDescription('flesch_kincaid', $score)['informasi'],      //* Score - Informasi
        'word_count'                    => $this->wordCount($content),                                                //* Word count
        'reading_time'                  => $this->readingTime($content),                                              //* Reading time
        'keyword_list'                  => $this->extractKeywords($content, $title)                                   //* Keyword list
      ],
      'analyze'     => [
        'keyphrase_in_introduction'     => $this->analyzeFocusKeyphraseInIntroduction($content, $keyphrase),          //* Keyphrase in introduction
        'keyphrase_density'             => $this->analyzeKeyphraseDensity($content, $keyphrase),                      //* Keyphrase density
        'keyphrase_distribution'        => $this->analyzeKeyphraseDistribution($content, $keyphrase),                 //* Keyphrase distribution
        'keyphrase_length'              => $this->analyzeKeyphraseLength($keyphrase),                                 //* Keyphrase length
        'keyphrase_in_subheadings'      => $this->analyzeKeyphraseInSubheadings($content, $keyphrase),                //* Keyphrase in subheadings
        'keyphrase_in_image_alt_tags'   => $this->analyzeImageAltTags($content, $keyphrase),                          //* Keyphrase in Image alt tags
        'keyphrase_in_page_title'       => $this->analyzeKeyphraseInTitle($title, $keyphrase),                        //* Keyphrase in page title
        'keyphrase_in_link'             => $this->analyzeLinkKeyphrase($content, $keyphrase),                         //* Keyphrase in link
        'keyphrase_in_slug'             => $this->analyzeKeyphraseInSlug($keyphrase, $slug),                          //* Keyphrase in slug
        'keyphrase_in_meta_description' => $this->analyzeKeyphraseInMetaDescription($meta_description, $keyphrase),   //* Keyphrase in Meta Description
        'previously_used_keyphrase'     => $this->analyzePreviouslyUsedKeyphrase($keyphrase, $list_keyphrase),        //* Previously used keyphrase
        'meta_description_length'       => $this->analyzeMetaDescriptionLength($meta_description),                    //* Meta Description length
        'outbound_links'                => $this->analyzeOutboundLinks($content, $this->domain),                      //* Outbound links
        'internal_links'                => $this->analyzeInternalLinks($content, $this->domain),                      //* Internal links
        'images'                        => $this->analyzeImages($content),                                            //* Images
        'text_length'                   => $this->analyzeWordCount($content),                                         //* Text length
        'seo_title_width'               => $this->analyzePageTitle($title),                                           //* SEO title width
      ],
      'readability' => [
        'word_complexity'               => $this->analyzeWordComplexity($content, $title),                            //* Word complexity
        'sentence_length'               => $this->analyzeSentenceLength($content),                                    //* Sentence length
        'paragraph_length'              => $this->analyzeParagraphLength($content),                                   //* Paragraph length
        'subheading_distribution'       => $this->analyzeSubheadingDistribution($content),                            //* Subheading distribution
        'consecutive_sentences'         => $this->analyzeConsecutiveSentences($content),                              //* Consecutive sentences
        'transition_words'              => $this->analyzeTransitionWords($content),                                   //* Transition words
        'passive_voice'                 => $this->analyzePassiveVoice($content),                                      //* Passive voice
      ]
    ];
  }

  public function calculateSuccessPercentage($data) {
    $results = [];
    foreach (['analyze', 'readability'] as $key) {
      $total        = count($data[$key]);
      $successCount = 0;
      foreach ($data[$key] as $subkey => $value) {
        if (isset($value['status']) && $value['status'] === 'success') {
          $successCount++;
        }
      }

      $percentage = ($successCount / $total) * 100;
      if ($percentage >= 71) {
        $info = 'success';
      } elseif ($percentage >= 31) {
        $info = 'warning';
      } else {
        $info = 'danger';
      }

      $results[$key] = [
        'total'      => $total,
        'success'    => $successCount,
        'percentage' => round($percentage, 0),
        'status'     => $info
      ];
    }
    return $results;
  }

  //! INSIGHT

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
    $all_text = strtolower(strip_tags($content) . ' ' . $title);
    $words = array_count_values(str_word_count($all_text, 1));
    arsort($words);
    return array_slice($words, 0, 10, true);
  }

  //* Flesch-Kincaid Grade Level lebih fokus pada tingkat pendidikan atau kelas sekolah yang dibutuhkan untuk memahami teks.
  /**
   *
   * SOURCE:
   * https://readable.com/readability/flesch-reading-ease-flesch-kincaid-grade-level/
   *
   **/
  public function fleschKincaidGrade($content) {
    $content   = strip_tags($content);
    $words     = str_word_count($content, 1);
    $sentences = preg_split('/[.!?]/', $content);
    $syllables = 0;
    foreach ($words as $word) {
      $syllables += $this->syllableCount($word);
    }
    $word_count     = count($words);
    $sentence_count = count($sentences);
    return 0.39 * ($word_count / $sentence_count) + 11.8 * ($syllables / $word_count) - 15.59;
  }

  //* Flesch Reading Ease memberikan angka yang lebih intuitif (semakin tinggi angkanya, semakin mudah dibaca).
  /**
   *
   * SOURCE:
   * https://readable.com/readability/flesch-reading-ease-flesch-kincaid-grade-level/
   *
   **/
  public function fleschReadingEase($content) {
    $content   = strip_tags($content);
    $words     = str_word_count($content, 1);
    $sentences = preg_split('/[.!?]/', $content);
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

  //! KEYPHRASE

  public function analyzeFocusKeyphraseInIntroduction($content, $keyphrase) {
    $content = strip_tags($content);
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
      $result['status']  = 'success';
      $result['message'] = 'Keyphrase ditemukan di paragraf pembuka. Ini bagus untuk SEO.';
    } else {
      $result['status']  = 'danger';
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
      $result['status']  = 'danger';
      $result['message'] = 'Keyphrase tidak ditemukan dalam konten.';
    } elseif ($density > 2.5) {
      $result['status']  = 'warning';
      $result['message'] = 'Kepadatan keyphrase terlalu tinggi. Kurangi penggunaan keyphrase.';
    } elseif ($density >= 0.5) {
      $result['status']  = 'success';
      $result['message'] = "Kepadatan keyphrase: Keyphrase ditemukan {$keyphrase_count} kali. Ini bagus!";
    } else {
      $result['status']  = 'warning';
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
      $result['status']  = 'success';
      $result['message'] = 'Bagus sekali! Keyphrase terdistribusi secara merata di seluruh konten.';
    } elseif (count($non_empty_sections) === 2) {
      $result['status']  = 'warning';
      $result['message'] = 'Keyphrase ditemukan di dua bagian. Pertimbangkan untuk mendistribusikannya lebih merata.';
    } elseif (count($non_empty_sections) === 1) {
      $result['status']  = 'danger';
      $result['message'] = 'Keyphrase hanya ditemukan di satu bagian. Sebarkan lebih merata di seluruh konten.';
    } else {
      $result['status']  = 'danger';
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
      $result['status']  = 'success';
      $result['message'] = 'Bagus sekali! Panjang keyphrase ideal untuk SEO.';
    } elseif ($keyphrase_length < 2) {
      $result['status']  = 'danger';
      $result['message'] = 'Keyphrase terlalu pendek. Pertimbangkan menambahkan kata untuk meningkatkan relevansi.';
    } elseif ($keyphrase_length > 4) {
      $result['status']  = 'warning';
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
      $result['status']  = 'success';
      $result['message'] = "{$keyphrase_count} dari subheading H2 dan H3 mencerminkan topik konten Anda. Kerja bagus!";
    } elseif ($keyphrase_count === 1) {
      $result['status']  = 'warning';
      $result['message'] = "Hanya 1 subheading H2 atau H3 yang mencerminkan topik konten Anda. Pertimbangkan menambahkannya ke lebih banyak subheading.";
    } else {
      $result['status']  = 'danger';
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
      $result['status']  = 'danger';
      $result['message'] = 'Tidak ada gambar pada halaman ini. Tambahkan gambar yang relevan untuk meningkatkan SEO.';
    } elseif ($images_with_keyphrase >= ceil($total_images / 2)) {
      $result['status']  = 'success';
      $result['message'] = "Gambar di halaman ini memiliki alt attribute dengan setidaknya setengah kata dari keyphrase Anda. Kerja bagus!";
    } elseif ($images_with_keyphrase > 0) {
      $result['status']  = 'warning';
      $result['message'] = "Beberapa gambar memiliki alt attribute yang mengandung keyphrase Anda. Pertimbangkan menambahkan alt attribute pada lebih banyak gambar.";
    } else {
      $result['status']  = 'danger';
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
        $result['status'] = 'success';
        $result['message'] = 'Keyphrase berada di awal judul. Ini sangat baik untuk SEO.';
      } else {
        // Keyphrase is not at the beginning
        $result['status'] = 'warning';
        $result['message'] = 'Keyphrase ada di judul, tetapi tidak di awal. Pindahkan ke awal untuk hasil terbaik.';
      }
    } else {
      // Keyphrase is not found in the title
      $result['status'] = 'danger';
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
      $result['status'] = 'danger';
      $result['message'] = 'Anda menautkan ke halaman lain dengan kata-kata yang ingin Anda rangkingkan. Jangan lakukan itu!';
    } else {
      // Keyphrase is not found in anchor text
      $result['status'] = 'success';
      $result['message'] = 'Tidak ada penggunaan kata kunci dalam teks anchor. Ini bagus untuk SEO.';
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
      $result['status']  = 'success';
      $result['message'] = 'Bagus! Tidak ada riwayat keyphrase sebelumnya.';
      return $result;
    }

    // Normalize the keyphrase and the list of used keyphrases
    $clean_keyphrase = strtolower(trim($keyphrase));
    $clean_used_keyphrases = array_map('strtolower', array_map('trim', $used_keyphrases));

    // Check if the keyphrase is in the list of used keyphrases
    if (in_array($clean_keyphrase, $clean_used_keyphrases, true)) {
      $result['previously_used'] = true;
      $result['status']          = 'danger';
      $result['message']         = 'Keyphrase sebelumnya telah digunakan. Jangan gunakan keyphrase lebih dari sekali.';
    } else {
      $result['status']  = 'success';
      $result['message'] = 'Bagus! Keyphrase ini belum pernah digunakan sebelumnya.';
    }

    return $result;
  }

  public function analyzeKeyphraseInSlug($keyphrase, $slug) {
    // Validate if $slug is not empty
    if (empty($slug)) {
      $result['status']  = 'danger';
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

    // Convert keyphrase into slug format (replace spaces with hyphens and remove special characters)
    $formatted_keyphrase = preg_replace('/[^a-z0-9\-]+/', '', str_replace(' ', '-', $clean_keyphrase));

    // Check if the keyphrase exists in the slug
    if (strpos($clean_slug, $formatted_keyphrase) !== false) {
      $result['keyphrase_in_slug'] = true;
      $result['status']  = 'success';
      $result['message'] = 'Keyphrase dalam slug: Kerja bagus!';
    } else {
      $result['status']  = 'danger';
      $result['message'] = 'Keyphrase tidak ditemukan dalam slug. Tambahkan untuk meningkatkan SEO.';
    }

    return $result;
  }

  public function analyzeKeyphraseInMetaDescription($meta_description, $keyphrase) {
    // Validate if $meta_description is not empty
    if (empty($meta_description)) {
      $result['status']  = 'danger';
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
      $result['status']  = 'success';
      $result['message'] = 'Keyphrase muncul dalam meta description. Bagus sekali!';
    } else {
      $result['status']  = 'danger';
      $result['message'] = 'Keyphrase tidak ditemukan dalam meta description. Tambahkan untuk meningkatkan relevansi SEO.';
    }

    return $result;
  }

  public function analyzeMetaDescriptionLength($meta_description) {
    // Calculate the length of the meta description
    $meta_description = html_entity_decode($meta_description);  // Decode HTML entities
    $meta_description = str_replace('&nbsp;', '', $meta_description);  // Remove non-breaking spaces
    $description_length = strlen($meta_description);

    $result = [
      'meta_description' => $meta_description,
      'length'           => $description_length,
      'status'           => '',
      'message'          => '',
    ];

    // Validate if $meta_description is not empty
    if (empty($meta_description)) {
      $result['status']  = 'danger';
      $result['message'] = 'Meta description kosong. Tambahkan untuk meningkatkan relevansi SEO.';
      return $result;
    }

    // Analyze the length and provide feedback
    if ($description_length > 156) {
      $result['status']  = 'danger';
      $result['message'] = 'Panjang meta description lebih dari 156 karakter. Kurangi panjangnya agar seluruh deskripsi terlihat.';
    } elseif ($description_length >= 120 && $description_length <= 156) {
      $result['status']  = 'success';
      $result['message'] = 'Panjang meta description sangat baik! Pastikan tetap relevan dengan konten.';
    } elseif ($description_length < 120 && $description_length > 0) {
      $result['status']  = 'warning';
      $result['message'] = 'Panjang meta description kurang dari ideal. Tambahkan beberapa kata lagi untuk menjelaskan konten lebih baik.';
    } else {
      $result['status']  = 'danger';
      $result['message'] = 'Meta description tidak ditemukan. Tambahkan deskripsi untuk meningkatkan SEO.';
    }

    return $result;
  }

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
        'status'               => 'danger',
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
      $result['status']  = 'success';
      $result['message'] = 'Setidaknya ada satu link yang diikuti';
    } elseif ($result['nofollowed_links'] > 0) {
      $result['status']  = 'warning';
      $result['message'] = 'Hanya link nofollow';
    } else {
      $result['status']  = 'danger';
      $result['message'] = 'Tidak ada link keluar';
    }

    return $result;
  }

  public function analyzeInternalLinks($content, $currentDomain) {
    $result = [
      'total_internal_links' => 0,
      'nofollowed_links'     => 0,
      'followed_links'       => 0,
      'status'               => 'danger', // Default traffic light status
      'message'              => 'Tidak ditemukan link internal', // Default message
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
        $result['status']  = 'success';
        $result['message'] = 'Internal link sudah dioptimalkan dengan link yang diikuti (followed).';
      } else {
        $result['status']  = 'warning';
        $result['message'] = 'Hanya ditemukan internal link nofollow. Pertimbangkan untuk menambahkan link yang diikuti (followed).';
      }
    } else {
      $result['status']  = 'danger';
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
        $result['status']  = 'success';
        $result['message'] = 'Semua gambar memiliki atribut alt yang baik.';
      } elseif ($result['images_with_alt'] > 0) {
        $result['status']  = 'warning';
        $result['message'] = 'Beberapa gambar tidak memiliki atribut alt. Tambahkan deskripsi alt untuk meningkatkan SEO.';
      } else {
        $result['status']  = 'danger';
        $result['message'] = 'Tidak ada gambar yang memiliki atribut alt. Tambahkan deskripsi alt untuk semua gambar.';
      }
    } else {
      $result['status']    = 'danger';
      $result['message']   = 'Tidak ada gambar ditemukan dalam konten. Tambahkan gambar untuk membuat konten lebih menarik.';
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
      $result['status']  = 'success';
      $result['message'] = 'Artikel memiliki jumlah kata yang sangat baik untuk SEO.';
    } elseif ($word_count >= 300) {
      $result['status']  = 'warning';
      $result['message'] = 'Jumlah kata cukup, tetapi lebih banyak kata akan meningkatkan kualitas SEO.';
    } else {
      $result['status']  = 'danger';
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
      $result['status']  = 'warning';
      $result['message'] = 'Panjang judul tidak ideal. Pastikan antara 50–60 karakter.';
    } elseif ($title_length >= 50 && $title_length <= 60) {
      $result['status']  = 'success';
      $result['message'] = 'Judul halaman sangat baik untuk SEO. Panjang ideal dan unik.';
    }

    return $result;
  }

  //! READABILITY

  public function analyzeWordComplexity($content, $title) {
    // Extract complex words
    $complex_words      = $this->extractComplexWords($content, $title);
    $total_words        = str_word_count(strip_tags($content));
    $complex_word_count = array_sum($complex_words);
    $percentage_complex = $total_words > 0 ? ($complex_word_count / $total_words) * 100 : 0;

    $result = [
      'complex_word_count' => $complex_word_count,
      'total_words'        => $total_words,
      'percentage_complex' => round($percentage_complex, 2),
      'status'             => '',
      'message'            => '',
    ];

    if ($percentage_complex > 15) {
        $result['status'] = 'warning';
        $result['message'] = 'Teks mengandung terlalu banyak kata kompleks. Pertimbangkan untuk menyederhanakannya.';
    } else {
        $result['status'] = 'success';
        $result['message'] = 'Anda tidak menggunakan terlalu banyak kata kompleks, membuat teks mudah dibaca. Kerja bagus!';
    }

    return $result;
  }
  private function extractComplexWords($content, $title, $min_length = 8) {
    // Combine content and title, normalize case
    $all_text = strtolower($content . ' ' . $title);

    // Count word occurrences
    $words = array_count_values(str_word_count($all_text, 1));

    // Filter for complex words based on length
    $complex_words = array_filter($words, function ($word) use ($min_length) {
      return strlen($word) >= $min_length;
    });

    // Sort by frequency
    arsort($complex_words);

    return $complex_words;
  }

  public function analyzeSentenceLength($text, $maxWords = 20) {
    // Define default thresholds
    $threshold = ['green' => 25, 'orange' => [25, 30], 'red' => 30];

    // Replace dots within links with a placeholder
    $text = preg_replace_callback('/(<a[^>]*>.*?<\/a>)/s', function($matches) {
      return str_replace('.', '{DOT}', $matches[0]);
    }, $text);

    // Tambahkan titik dan spasi pada elemen heading
    $text = preg_replace('/(<h[1-6][^>]*>)(.*?)(<\/h[1-6]>)/', ' $1 $2. $3', $text);
    // Tambahkan titik dan spasi pada elemen paragraf
    $text = preg_replace('/(<p[^>]*>)(.*?)(<\/p>)/', ' $1 $2. $3', $text);
    // Ganti format agar setelah titik ada '+'
    $text = preg_replace('/(\w+)\.(\w+)/', '$1. +$2', $text);
    // Pastikan ada spasi sebelum titik dan setelahnya di luar elemen HTML
    $text = preg_replace('/\.(?=\S)/', '. +', $text);
    // Tambahkan spasi awal untuk heading atau paragraf, jika tidak ada sebelumnya
    $text = preg_replace('/(?<!\s)(<h[1-6]|<p)/', ' $1', $text);

    $sentences = preg_split('/(?<=[.?!])\s+(?![^<]*<\/a>)/', strip_tags($text), -1, PREG_SPLIT_NO_EMPTY);

    // Restore dots inside links
    $sentences = array_map(function($sentence) {
      return str_replace('{DOT}', '.', $sentence); // Restore {DOT} back to '.'
    }, $sentences);

    $totalSentences     = count($sentences);
    $longSentences      = 0;
    $exceedingSentences = [];

    foreach ($sentences as $sentence) {
      preg_match_all('/\b[\w\']+\b/u', $sentence, $matches);
      $wordCount = count($matches[0]);
      if ($wordCount > $maxWords) {
        $longSentences++;
        $exceedingSentences[] = [
          'sentence' => $sentence,
          'wordCount' => $wordCount
        ];
      }
    }

    // Calculate the percentage of long sentences
    $percentage = $totalSentences > 0 ? ($longSentences / $totalSentences) * 100 : 0;

    // Determine the status and message
    if ($percentage > $threshold['red']) {
      $score   = 3;
      $status  = 'danger';
      $message = "$longSentences dari $totalSentences total kalimat memiliki lebih dari $maxWords kata, yang melebihi batas maksimum yang direkomendasikan sebesar $maxWords kata. Cobalah untuk memperpendek kalimat.";
    } elseif ($percentage >= $threshold['orange'][0] && $percentage <= $threshold['orange'][1]) {
      $score   = 6;
      $status  = 'warning';
      $message = "$longSentences dari $totalSentences total kalimat memiliki lebih dari $maxWords kata, yang melebihi batas maksimum yang direkomendasikan sebesar $maxWords kata. Cobalah untuk memperpendek kalimat.";
    } else {
      $score   = 9;
      $status  = 'success';
      $message = "Sangat baik!";
    }

    // Return the result as a table-like array
    return [
      'status'             => $status ,
      'message'            => $message ,
      'score'              => $score ,
      'percentage'         => round($percentage, 2),
      // 'exceedingSentences' => $exceedingSentences, //! Uncomment hanya saat ingin Debug saja
      'criterion'          => $percentage > $threshold['red'] ? "> {$threshold['red']}%" :
                              ($percentage >= $threshold['orange'][0] ? "Between {$threshold['orange'][0]} and {$threshold['orange'][1]}%" : "≤ {$threshold['green']}%")
    ];
  }

  public function analyzeParagraphLength($content, $max_sentences = 4) {
    // Regex untuk menangkap tag HTML <p>, <h1>-<h6>, dan lainnya
    preg_match_all('/<(p|h[1-6]|[^>]+)>(.*?)<\/\1>/is', $content, $matches, PREG_SET_ORDER);

    $total_tags        = count($matches);
    $long_tags         = [];
    $tag_sentence_data = [];

    foreach ($matches as $match) {
      $tag_name   = $match[1]; // Nama tag
      $tag_content = strip_tags($match[2]); // Konten tag tanpa tag HTML di dalamnya

      // Hitung jumlah kalimat
      $sentences = preg_split('/(?<=[.!?])\s+/', $tag_content, -1, PREG_SPLIT_NO_EMPTY);
      $sentence_count = count($sentences);

      // Tambahkan data tag dan jumlah kalimat
      $tag_sentence_data[] = [
        'tag'            => $tag_name,
        'content'        => $tag_content,
        'sentence_count' => $sentence_count,
      ];

      // Cek apakah tag memiliki jumlah kalimat lebih dari $max_sentences
      if ($sentence_count > $max_sentences) {
        $long_tags[] = [
          'tag'            => $tag_name,
          'content'        => $tag_content,
          'sentence_count' => $sentence_count,
        ];
      }
    }

    $long_tag_count = count($long_tags);

    $result = [
      'total_tags'        => $total_tags,
      'long_tag_count'    => $long_tag_count,
      // 'tag_sentence_data' => $tag_sentence_data,   // Semua data tag
      // 'long_tags'         => $long_tags,           // Hanya tag yang panjang
      'status'            => '',
      'message'           => '',
    ];

    if ($long_tag_count > 0) {
      $result['status']    = 'warning';
      $result['message']   = 'Beberapa paragraf terlalu panjang. Usahakan agar setiap paragraf tidak lebih dari ' . $max_sentences . ' kalimat.';
    } else {
      $result['status']    = 'success';
      $result['message']   = 'Tidak ada paragraf yang terlalu panjang. Kerja bagus!';
    }

    return $result;
  }

  public function analyzeSubheadingDistribution($content) {
    // Extract headings from the content
    preg_match_all('/<h[1-6][^>]*>.*?<\/h[1-6]>/', $content, $matches);
    $headings = $matches[0];

    // Remove HTML tags to count word distribution between headings
    $clean_content     = strip_tags($content);
    $word_count        = str_word_count($clean_content);
    $heading_positions = [];

    foreach ($headings as $heading) {
      $position = strpos($clean_content, strip_tags($heading));
      $heading_positions[] = $position;
    }

    $heading_distribution = count($headings) > 0 
      ? round(($word_count / (count($headings) + 1)), 2)
      : $word_count;

    $result = [
      'total_headings'       => count($headings),
      'word_count'           => $word_count,
      'heading_distribution' => $heading_distribution,
      'status'               => '',
      'message'              => '',
    ];

    // Evaluate distribution
    if (count($headings) === 0) {
      $result['status'] = 'danger';
      $result['message'] = 'Tidak ada subjudul. Gunakan subjudul untuk membuat konten lebih terstruktur dan mudah dibaca.';
    } elseif ($heading_distribution > 300) {
      $result['status'] = 'warning';
      $result['message'] = 'Distribusi subjudul tidak seimbang. Tambahkan lebih banyak subjudul untuk mengatur teks yang panjang.';
    } else {
      $result['status'] = 'success';
      $result['message'] = 'Distribusi subjudul bagus!';
    }

    return $result;
  }

  public function analyzeConsecutiveSentences($content) {
    // Normalize content and split it into sentences
    $clean_content = strip_tags($content); // Remove HTML tags
    $sentences = preg_split('/(?<=[.!?])\s+/', $clean_content, -1, PREG_SPLIT_NO_EMPTY);

    $consecutive_count = 0;
    $previous_start_words = [];
    $max_consecutive = 0;

    foreach ($sentences as $sentence) {
      // Get the first word of each sentence
      $words = explode(' ', trim($sentence));
      $start_word = strtolower($words[0]);

      if (in_array($start_word, $previous_start_words)) {
        $consecutive_count++;
        $max_consecutive = max($max_consecutive, $consecutive_count);
      } else {
        $consecutive_count = 1; // Reset counter for consecutive sentences
      }

      $previous_start_words = [$start_word];
    }

    $result = [
      'total_sentences' => count($sentences),
      'max_consecutive' => $max_consecutive,
      'status'          => '',
      'message'         => '',
    ];

    // Evaluate sentence variety
    if ($max_consecutive > 2) {
      $result['status'] = 'warning';
      $result['message'] = 'Ada beberapa kalimat berturut-turut yang dimulai dengan kata yang sama. Pertimbangkan untuk menambahkan variasi.';
    } elseif ($max_consecutive === 2) {
      $result['status'] = 'warning';
      $result['message'] = 'Beberapa kalimat dimulai dengan kata yang sama, tetapi variasi sudah cukup baik.';
    } else {
      $result['status'] = 'success';
      $result['message'] = 'Kalimat berturut-turut: Ada cukup variasi dalam kalimat Anda. Bagus!';
    }

    return $result;
  }

  public function analyzeTransitionWords($content) {
    // Define transition words (single and multiple words)
    $singleWords = [
      "adakalanya",
      "agak",
      "agar",
      "akhirnya",
      "alhasil",
      "andaikan",
      "bahkan",
      "bahwasannya",
      "berikut",
      "betapapun",
      "biarpun",
      "biasanya",
      "contohnya",
      "dahulunya",
      "diantaranya",
      "dikarenakan",
      "disebabkan",
      "dulunya",
      "faktanya",
      "hasilnya",
      "intinya",
      "jadi",
      "jua",
      "juga",
      "kadang-kadang",
      "kapanpun",
      "karena",
      "karenanya",
      "kedua",
      "kelak",
      "kemudian",
      "kesimpulannya",
      "khususnya",
      "langsung",
      "lantaran",
      "maka",
      "makanya",
      "masih",
      "memang",
      "meski",
      "meskipun",
      "misalnya",
      "mulanya",
      "nantinya",
      "nyatanya",
      "pendeknya",
      "pertama",
      "ringkasnya",
      "rupanya",
      "seakan-akan",
      "sebaliknya",
      "sebelum",
      "sebetulnya",
      "sedangkan",
      "segera",
      "sehingga",
      "sekali-sekali",
      "sekalipun",
      "sekiranya",
      "selagi",
      "selain",
      "selama",
      "selanjutnya",
      "semasa",
      "semasih",
      "semenjak",
      "sementara",
      "semula",
      "sepanjang",
      "serasa",
      "seraya",
      "seringkali",
      "sesungguhnya",
      "setelahnya",
      "seterusnya",
      "setidak-tidaknya",
      "setidaknya",
      "sewaktu-waktu",
      "sewaktu",
      "tadinya",
      "tentunya",
      "terakhir",
      "terdahulu",
      "terlebih",
      "ternyata",
      "terpenting",
      "terutama",
      "terutamanya",
      "tetapi",
      "umpamanya",
      "umumnya",
      "utamanya",
      "walau",
      "walaupun",
      "yaitu",
      "yakni",
      "akibatnya",
      "hingga",
      "kadang",
      "kendatipun",
      "ketiga",
      "lainnya",
      "manakala",
      "namun",
      "pastinya",
      "pertama-tama",
      "sampai-sampai",
      "sebaliknya",
      "sebelumnya",
      "sebetulnya",
      "sesekali"
    ];

    $multipleWords = [
      "agar supaya",
      "akan tetapi",
      "apa lagi",
      "asal saja",
      "bagaimanapun juga",
      "bahkan jika",
      "bahkan lebih",
      "begitu juga",
      "berbeda dari",
      "biarpun begitu",
      "biarpun demikian",
      "bilamana saja",
      "cepat atau lambat",
      "dalam hal ini",
      "dalam jangka panjang",
      "dalam kasus ini",
      "dalam kasus lain",
      "dalam kedua kasus",
      "dalam kenyataannya",
      "dalam pandangan",
      "dalam situasi ini",
      "dalam situasi seperti itu",
      "dan lagi",
      "dari awal",
      "dari pada",
      "dari waktu ke waktu",
      "demikian juga",
      "demikian pula",
      "dengan serentak",
      "dengan cara yang sama",
      "dengan jelas",
      "dengan kata lain",
      "dengan ketentuan",
      "dengan nyata",
      "dengan panjang lebar",
      "dengan pemikiran ini",
      "dengan syarat bahwa",
      "dengan terang",
      "di pihak lain",
      "di sisi lain",
      "dibandingkan dengan",
      "disebabkan oleh",
      "ditambah dengan",
      "hanya jika",
      "harus diingat",
      "hasil dari",
      "hingga kini",
      "kalau tidak",
      "kalau-kalau",
      "kali ini",
      "kapan saja",
      "karena alasan itulah",
      "karena alasan tersebut",
      "kecuali kalau",
      "kendatipun begitu",
      "kendatipun demikian",
      "lebih jauh",
      "lebih lanjut",
      "maka dari itu",
      "meskipun demikian",
      "oleh karena itu",
      "oleh karenanya",
      "oleh sebab itu",
      "pada akhirnya",
      "pada awalnya",
      "pada dasarnya",
      "pada intinya",
      "pada kenyataannya",
      "pada kesempatan ini",
      "pada mulanya",
      "pada saat ini",
      "pada saat",
      "pada situasi ini",
      "pada umumnya",
      "pada waktu yang sama",
      "pada waktunya",
      "paling tidak",
      "pendek kata",
      "penting untuk disadari",
      "poin penting lainnya",
      "saat ini",
      "sama halnya",
      "sama pentingnya",
      "sama sekali",
      "sampai sekarang",
      "sebab itu",
      "sebagai akibatnya",
      "sebagai contoh",
      "sebagai gambaran",
      "sebagai gantinya",
      "sebagai hasilnya",
      "sebagai tambahan",
      "sebelum itu",
      "secara bersamaan",
      "secara eksplisit",
      "secara keseluruhan",
      "secara khusus",
      "secara menyeluruh",
      "secara signifikan",
      "secara singkat",
      "secara umum",
      "sejalan dengan ini",
      "sejalan dengan itu",
      "sejauh ini",
      "sekali lagi",
      "sekalipun begitu",
      "sekalipun demikian",
      "sementara itu",
      "seperti yang bisa dilihat",
      "seperti yang sudah saya katakan",
      "seperti yang sudah saya tunjukkan",
      "sesudah itu",
      "setelah ini",
      "setelah itu",
      "tak pelak lagi",
      "tanpa menunda-nunda lagi",
      "tentu saja",
      "terutama sekali",
      "tidak perlu dipertanyakan lagi",
      "tidak sama",
      "tidak seperti",
      "untuk alasan ini",
      "untuk alasan yang sama",
      "untuk memperjelas",
      "untuk menekankan",
      "untuk menyimpulkan",
      "untuk satu hal",
      "untuk sebagian besar",
      "untuk selanjutnya",
      "untuk tujuan ini",
      "walaupun demikian",
      "yang lain",
      "yang terakhir",
      "yang terpenting",
      "begitu pula",
      "berbeda dengan",
      "betapapun juga",
      "dalam hal itu",
      "di samping itu",
      "hal pertama yang perlu diingat",
      "kadang kala",
      "karena itu",
      "lagi pula",
      "lambat laun",
      "mengingat bahwa",
      "meskipun begitu",
      "pada umumnya",
      "pada waktu",
      "saat ini juga",
      "sampai saat ini",
      "sebagian besar",
      "secara terperinci",
      "selain itu",
      "seperti yang sudah dijelaskan",
      "seperti yang tertera di",
      "tak seperti",
      "tanpa memperhatikan",
      "tentu saja",
      "untuk memastikan",
      "untuk menggambarkan",
      "walaupun begitu"
    ];

    // Normalize content
    $clean_content = strtolower(strip_tags($content));

    // Count transition words
    $transition_count = 0;

    foreach ($singleWords as $word) {
      $transition_count += substr_count($clean_content, $word);
    }

    foreach ($multipleWords as $phrase) {
      $transition_count += substr_count($clean_content, $phrase);
    }

    $total_sentences = max(1, count(preg_split('/(?<=[.!?])\s+/', $clean_content, -1, PREG_SPLIT_NO_EMPTY)));
    $percentage = ($transition_count / $total_sentences) * 100;

    $result = [
      'total_sentences'  => $total_sentences,
      'transition_count' => $transition_count,
      'percentage'       => round($percentage, 2),
      'status'           => '',
      'message'          => '',
    ];

    // Evaluate transition word usage
    if ($percentage < 30) {
      $result['status'] = 'danger';
      $result['message'] = 'Tidak ada kalimat yang mengandung kata transisi. Gunakan beberapa.';
    } else {
      $result['status'] = 'success';
      $result['message'] = 'Bagus! Anda menggunakan kata transisi dengan baik.';
    }

    return $result;
  }

  public function analyzePassiveVoice($content) {
    // Daftar kata kerja pasif
    $passiveMarkers = ['diberi', 'dilakukan', 'dikatakan', 'ditulis', 'dibuat', 'diberikan', 'diperoleh', 'didapat', 'dipilih', 'digunakan', 'diambil', 'dijelaskan', 'dilihat', 'dipercaya', 'dikirim', 'ditemukan', 'dilaksanakan'];

    $nonPassiveWords = [
      "diskontinuitas",
      "diskualifikasi",
      "diskriminatif",
      "diskriminator",
      "digitalisasi",
      "disinformasi",
      "disintegrasi",
      "diskriminasi",
      "disorientasi",
      "distabilitas",
      "diktatorial",
      "disinfektan",
      "disinsentif",
      "diskrepansi",
      "distributor",
      "diagnostik",
      "dialketika",
      "diktatoris",
      "dinosaurus",
      "diplomatik",
      "diplomatis",
      "direktorat",
      "dirgantara",
      "disimilasi",
      "diskontinu",
      "diskulpasi",
      "disparitas",
      "dispensasi",
      "distilator",
      "distingtif",
      "distribusi",
      "diversitas",
      "diafragma",
      "diagnosis",
      "diakritik",
      "diakronis",
      "dialektal",
      "dialektik",
      "dialektis",
      "digenesis",
      "digitalis",
      "dilematik",
      "diminutif",
      "dinamisme",
      "dingklang",
      "diplomasi",
      "dirgahayu",
      "disertasi",
      "disfungsi",
      "diskredit",
      "diskursif",
      "disleksia",
      "dislokasi",
      "dismutasi",
      "disonansi",
      "disosiasi",
      "dispenser",
      "disposisi",
      "distilasi",
      "distingsi",
      "divestasi",
      "diabeter",
      "diagonal",
      "dialisis",
      "diameter",
      "diaspora",
      "difraksi",
      "digestif",
      "diglosia",
      "dikotomi",
      "diktator",
      "dilatasi",
      "dimorfik",
      "dinamika",
      "dioksida",
      "diopsida",
      "diplomat",
      "direktur",
      "disentri",
      "disensus",
      "disiplin",
      "diskotek",
      "diskresi",
      "dispersi",
      "disrupsi",
      "distansi",
      "distorsi",
      "diagram",
      "difabel",
      "digdaya",
      "digital",
      "digresi",
      "diletan",
      "dimensi",
      "dinamik",
      "dinamis",
      "dinamit",
      "dinasti",
      "dioksin",
      "diorama",
      "diploma",
      "diptera",
      "direksi",
      "dirigen",
      "disagio",
      "disiden",
      "disjoki",
      "diskoid",
      "diskusi",
      "disuasi",
      "dividen",
      "diadem",
      "diakon",
      "dialek",
      "dialog",
      "diaper",
      "diayah",
      "diesel",
      "dilasi",
      "dinamo",
      "diniah",
      "diorit",
      "diare",
      "diode",
      "didih",
      "didik",
      "didis",
      "digit",
      "dikau",
      "dikir",
      "diksi",
      "dikte",
      "dinas",
      "dipan",
      "dirah",
      "direk",
      "disko",
      "dinda",
      "difusi",
      "dilema",
      "dingin",
      "diniah",
      "diorit",
      "dirham",
      "disket",
      "diskon",
      "divisi",
      "diftong",
      "difteri",
      "dinding",
      "dingkis",
      "dingkit",
      "dioksin",
      "diorama",
      "diploma",
      "dirigen",
      "disiden",
      "displin",
      "disjoki",
      "diskusi",
      "distrik",
      "dividen",
      "digestif",
      "diglosia",
      "dikotomi",
      "dingklik",
      "dioksida",
      "diplomat",
      "direktur",
      "disentri",
      "diskresi",
      "disorder",
      "dispersi",
      "distansi",
      "disrupsi",
      "divergen",
      "dingklang",
      "diplomasi",
      "dirgahayu",
      "disertasi",
      "disfungsi",
      "disilabik",
      "diskredit",
      "disleksia",
      "dislokasi",
      "disosiasi",
      "dispenser",
      "disposisi",
      "distilasi",
      "dinosaurus",
      "diplomatik",
      "diplomatis",
      "dirgantara",
      "disimilasi",
      "diskontinu",
      "disparitas",
      "distilator",
      "distribusi",
      "divergensi",
      "diversitas",
      "disabilitas",
      "disinfektan",
      "diskrepansi",
      "disintegrasi",
      "diskriminasi",
      "diskriminatif",
      "diskontinuitas",
      "diskualifikasi",
    ];

    // Pisahkan teks menjadi kalimat berdasarkan tanda baca
    $sentences = preg_split('/(?<=[.?!])\s+/', strip_tags($content), -1, PREG_SPLIT_NO_EMPTY);

    $totalSentences = count($sentences);
    $passiveCount = 0;

    foreach ($sentences as $sentence) {
      $isPassive = false;

      foreach ($passiveMarkers as $marker) {
        if (stripos($sentence, $marker) !== false) {
          // Cek apakah kata adalah bagian dari daftar kata non-pasif
          $words = explode(' ',
            strtolower($sentence)
          );
          $containsNonPassive = false;
          foreach ($words as $word) {
            if (in_array($word, $nonPassiveWords)) {
              $containsNonPassive = true;
              break;
            }
          }
          if (!$containsNonPassive) {
            $isPassive = true;
            break;
          }
        }
      }
      if ($isPassive) {
        $passiveCount++;
      }
    }

    // Cegah pembagian oleh nol
    if ($totalSentences === 0) {
      return [
        'status'  => 'Tidak Ada Konten',
        'score'   => 0,
        'message' => 'Teks tidak mengandung kalimat yang dapat dianalisis.',
      ];
    }

    // Hitung persentase kalimat pasif
    $percentage = round(($passiveCount / $totalSentences) * 100, 0);

    // Tentukan skor dan pesan
    if ($percentage <= 10) {
      $status  = 'success';
      $score   = 9;
      $message = 'Penggunaan kalimat aktif sudah cukup baik. Lanjutkan!';
    } elseif ($percentage > 10 && $percentage <= 15) {
      $status  = 'warning';
      $score   = 6;
      $message = "{$percentage}% dari kalimat adalah pasif, coba gunakan lebih banyak kalimat aktif.";
    } else {
      $status  = 'danger';
      $score   = 3;
      $message = "{$percentage}% dari kalimat adalah pasif, yang melebihi batas yang direkomendasikan. Usahakan mengganti dengan kalimat aktif.";
    }

    return [
      'status'          => $status,
      'score'           => $score,
      'message'         => $message,
      'passive_count'   => $passiveCount,
      'total_sentences' => $totalSentences,
      'percentage'      => $percentage,
    ];
  }
}