<?php

namespace Database\Seeders;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use Illuminate\Database\Seeder;

class NewsArticlesSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Luật & nhập môn' => [
                'slug' => 'luat-nhap-mon',
                'description' => 'Luật cơ bản, cách tính điểm và hướng dẫn bắt đầu chơi pickleball.',
            ],
            'Chiến thuật sân đấu' => [
                'slug' => 'chien-thuat-san-dau',
                'description' => 'Mẹo di chuyển, phối hợp đôi và xử lý bóng trong trận pickleball.',
            ],
            'Văn hóa cộng đồng' => [
                'slug' => 'van-hoa-cong-dong',
                'description' => 'Cách chơi giao lưu vui vẻ, an toàn và tôn trọng mọi trình độ.',
            ],
            'Dụng cụ & sức khỏe' => [
                'slug' => 'dung-cu-suc-khoe',
                'description' => 'Gợi ý chọn trang bị cơ bản và chuẩn bị thể lực trước khi lên sân.',
            ],
        ];

        foreach ($categories as $name => $attributes) {
            BlogCategory::query()->updateOrCreate(['slug' => $attributes['slug']], [
                'name' => $name,
                'description' => $attributes['description'],
            ]);
        }

        $articles = [
            [
                'title' => 'Luật pickleball cơ bản: 5 điều người mới cần nhớ trước trận đầu',
                'slug' => 'luat-pickleball-co-ban-cho-nguoi-moi',
                'excerpt' => 'Nắm cách giao bóng chéo sân, luật hai lần nảy, khu vực kitchen và cách tính điểm để tự tin bước vào trận pickleball đầu tiên tại TP.HCM.',
                'cover_image_path' => 'blog/news/pickleball-luat-cho-nguoi-moi.png',
                'cover_image_alt' => 'Người chơi pickleball mới tập luyện cùng huấn luyện viên trên sân trong nhà.',
                'meta_title' => 'Luật pickleball cơ bản cho người mới | BALLBURN',
                'meta_description' => 'Hướng dẫn luật pickleball dễ hiểu: giao bóng, luật hai lần nảy, kitchen, lỗi thường gặp và cách tính điểm cho người mới chơi.',
                'category' => 'Luật & nhập môn',
                'days_ago' => 12,
                'body' => <<<'HTML'
<p><strong>Tóm tắt nhanh:</strong> Người mới chơi pickleball chỉ cần nhớ năm điểm: giao bóng chéo sân; bóng giao và cú trả giao đều phải nảy một lần; không volley trong kitchen; bóng chạm vạch thường được tính là trong sân; và cách tính điểm có thể khác giữa trận giao lưu với giải đấu.</p>
<p>Pickleball kết hợp những yếu tố quen thuộc của các môn vợt nhưng có luật riêng. Nếu bạn đang tìm hiểu pickleball tại TP.HCM, hãy đọc phần tóm tắt dưới đây trước khi đặt sân hoặc tham gia buổi chơi đầu tiên.</p>
<h2>1. Giao bóng chéo sân như thế nào?</h2>
<p>Ở nội dung đôi, người giao đứng sau đường cuối sân và đưa bóng chéo sang ô giao bóng đối diện. Theo luật thi đấu của USA Pickleball, cú giao phải hợp lệ theo một trong các kiểu giao được cho phép; người mới nên tập động tác kiểm soát, đưa bóng sâu và ổn định trước khi cố giao mạnh.</p>
<h2>2. Luật hai lần nảy là gì?</h2>
<p>Sau cú giao, đội nhận phải để bóng nảy rồi mới trả. Đội giao cũng phải để cú trả nảy trước khi đánh tiếp. Sau hai lần nảy này, hai bên được chọn volley hoặc đánh bóng sau khi nảy. Quy tắc này tạo thời gian cho cả hai đội tiến vào vị trí và bắt đầu rally.</p>
<h2>3. Kitchen có cấm bước vào không?</h2>
<p>Kitchen là vùng không volley sát lưới. Bạn có thể bước vào vùng này để đánh một quả bóng đã nảy, nhưng không được volley khi đang chạm vùng hoặc vạch kitchen. Cũng cần giữ thăng bằng sau cú đánh: nếu đà người khiến bạn chạm kitchen sau khi volley, đó vẫn có thể là lỗi.</p>
<h2>4. Bóng chạm vạch là trong hay ngoài?</h2>
<p>Phần lớn đường biên được tính là trong nếu bóng chạm vạch. Ngoại lệ quan trọng là vạch kitchen trong cú giao bóng: bóng giao chạm vạch này bị xem là giao ngắn và lỗi. Khi chơi giao lưu, hãy thống nhất cách gọi bóng rõ ràng và tôn trọng quyết định của người ở gần bóng nhất.</p>
<h2>5. Tính điểm ra sao?</h2>
<p>Trong cách tính điểm side-out truyền thống, chỉ đội đang giao bóng mới ghi điểm; trận thường chơi đến 11 và phải hơn đối thủ ít nhất 2 điểm. Một số giải hoặc nhóm chơi dùng rally scoring, nơi mỗi rally đều có điểm. Vì vậy, hãy hỏi trước khi bắt đầu xem nhóm đang dùng thể thức nào.</p>
<h2>Câu hỏi thường gặp</h2>
<h3>Người mới có cần thuộc hết luật trước khi ra sân không?</h3><p>Không cần. Hãy nhớ luật hai lần nảy, kitchen và cách giao bóng cơ bản; người chơi cùng sân có thể nhắc thêm những tình huống phát sinh.</p>
<h3>Luật thi đấu có thể thay đổi không?</h3><p>Có. Luật chính thức được cập nhật định kỳ. Khi tham gia giải, hãy kiểm tra rulebook và điều lệ của ban tổ chức thay vì chỉ dựa vào hướng dẫn nhập môn.</p>
<p><strong>Gợi ý thực hành:</strong> Trước trận đầu tại một câu lạc bộ ở TP.HCM, hãy dành vài phút tập giao bóng và trả giao ở tốc độ vừa phải. Khi đã hiểu nhịp bóng, trận đấu sẽ dễ theo dõi hơn nhiều.</p>
<p><small>Nguồn tham khảo: <a href="https://usapickleball.org/rules/summary/" target="_blank" rel="noopener noreferrer">USA Pickleball — Rules Summary</a> và <a href="https://usapickleball.org/rules/" target="_blank" rel="noopener noreferrer">Official Rulebook</a>. Bài viết là hướng dẫn nhập môn, không thay thế luật của giải đấu cụ thể.</small></p>
HTML,
            ],
            [
                'title' => 'Chiến thuật kitchen trong pickleball: đứng đâu và di chuyển thế nào?',
                'slug' => 'chien-thuat-kitchen-trong-pickleball',
                'excerpt' => 'Hiểu vùng kitchen, phối hợp cùng đồng đội và tiến lên vạch không volley đúng thời điểm để kiểm soát rally pickleball tốt hơn.',
                'cover_image_path' => 'blog/news/pickleball-chien-thuat-kitchen.png',
                'cover_image_alt' => 'Bốn người chơi pickleball đánh đôi và kiểm soát bóng gần khu vực kitchen.',
                'meta_title' => 'Chiến thuật kitchen pickleball dễ áp dụng | BALLBURN',
                'meta_description' => 'Tìm hiểu kitchen pickleball, cách đứng sát vạch non-volley zone, di chuyển đồng bộ và tránh mắc lỗi khi volley trong trận đôi.',
                'category' => 'Chiến thuật sân đấu',
                'days_ago' => 10,
                'body' => <<<'HTML'
<p><strong>Tóm tắt nhanh:</strong> Kitchen là vùng không volley sát lưới. Mục tiêu thường gặp trong đánh đôi là tiến lên vạch kitchen sau khi xử lý đúng luật hai lần nảy, giữ tư thế sẵn sàng và di chuyển ngang đồng bộ với đồng đội. Đứng gần vạch giúp bạn phản ứng sớm, nhưng không có nghĩa là được volley khi chân chạm vùng cấm.</p>
<h2>Kitchen trong pickleball là gì?</h2>
<p>Kitchen là cách gọi thông dụng của non-volley zone (NVZ), khu vực kéo dài 7 feet, khoảng 2,13 m, từ lưới ở mỗi bên sân. Luật cấm volley khi người chơi hoặc vật họ đang mang chạm vùng này. Bạn vẫn có thể bước vào kitchen để đánh bóng sau khi bóng đã nảy.</p>
<h2>Khi nào nên tiến lên vạch kitchen?</h2>
<p>Trong đánh đôi, người nhận bóng thường có thể bắt đầu gần vạch kitchen, còn đồng đội đứng sau để trả giao. Đội giao cần chờ bóng giao và cú trả đều nảy theo luật hai lần nảy, sau đó tìm cơ hội tiến lên. Hãy tiến theo nhịp bóng và giữ thăng bằng thay vì lao lên bất kể đối thủ chuẩn bị đánh.</p>
<h2>Ba thói quen giúp kiểm soát khu vực lưới</h2>
<ul><li><strong>Giữ paddle trước người:</strong> tư thế sẵn sàng giúp rút ngắn thời gian phản ứng.</li><li><strong>Di chuyển theo bóng:</strong> hai người trong đội dịch chuyển ngang cùng nhau, hạn chế mở khoảng trống giữa sân.</li><li><strong>Chờ bóng phù hợp:</strong> bóng thấp thường nên được xử lý mềm và có kiểm soát; tránh cố đập những quả bóng đang ở vị trí bất lợi.</li></ul>
<h2>Những lỗi thường gặp ở kitchen</h2>
<p>Lỗi phổ biến là volley khi đang đứng trên vạch kitchen, bước vào vùng do đà sau cú volley, hoặc để hai người cùng dồn vào một phía. Khi đánh đôi, hãy trao đổi ngắn gọn như “của tôi”, “của bạn” để tránh va chạm và bỏ trống phần sân còn lại.</p>
<h2>Câu hỏi thường gặp</h2>
<h3>Có được đứng trong kitchen không?</h3><p>Được. Bạn chỉ phạm lỗi khi volley trong lúc chạm kitchen hoặc vi phạm liên quan đến đà sau cú volley.</p>
<h3>Người mới nên đứng sát lưới ngay từ đầu không?</h3><p>Hãy bắt đầu ở vị trí phù hợp với luật và cú đánh tiếp theo. Tiến lên có kiểm soát sau khi xử lý cú trả giao thường giúp người mới giữ thăng bằng tốt hơn.</p>
<p>Ở các buổi chơi đôi tại TP.HCM, hãy tập một bài đơn giản: hai người đứng song song, đánh bóng nhẹ qua lại và cùng bước ngang theo hướng bóng. Bài tập này giúp phối hợp vị trí trước khi tăng tốc độ rally.</p>
<p><small>Nguồn tham khảo: <a href="https://usapickleball.org/rules/summary/" target="_blank" rel="noopener noreferrer">USA Pickleball — Rules Summary</a> và <a href="https://usapickleball.org/pickleball-skills/level-two/pickleball-basics-positioning-tips/" target="_blank" rel="noopener noreferrer">Positioning, Patience, and Kitchen Line Strategy</a>.</small></p>
HTML,
            ],
            [
                'title' => 'Văn hóa chơi pickleball giao lưu: 7 phép lịch sự trên sân đôi',
                'slug' => 'van-hoa-choi-pickleball-giao-luu',
                'excerpt' => 'Từ cách xoay vòng sân đến gọi bóng và giao tiếp với đồng đội: những nguyên tắc nhỏ giúp buổi pickleball giao lưu vui vẻ, công bằng hơn.',
                'cover_image_path' => 'blog/news/pickleball-cong-dong-giao-luu.png',
                'cover_image_alt' => 'Nhóm người chơi nhiều độ tuổi vui vẻ trò chuyện sau một trận pickleball giao lưu.',
                'meta_title' => '7 phép lịch sự khi chơi pickleball giao lưu | BALLBURN',
                'meta_description' => 'Cẩm nang văn hóa pickleball giao lưu: xoay vòng sân công bằng, gọi bóng rõ ràng, tôn trọng trình độ và giao tiếp tích cực.',
                'category' => 'Văn hóa cộng đồng',
                'days_ago' => 8,
                'body' => <<<'HTML'
<p><strong>Tóm tắt nhanh:</strong> Chơi giao lưu vui nhất khi mọi người chia lượt công bằng, gọi bóng trung thực, tôn trọng trình độ và giữ nhịp trận đấu. Người mới không cần đánh thật hay để được chào đón; chỉ cần giao tiếp rõ ràng và sẵn sàng học luật cùng bạn chơi.</p>
<h2>1. Hỏi cách xoay vòng trước khi vào sân</h2><p>Mỗi câu lạc bộ có thể dùng cách chờ lượt khác nhau: danh sách, bảng xoay vòng hoặc ghép nhóm theo số người. Hãy hỏi người phụ trách trước để không vô tình chiếm sân quá lâu.</p>
<h2>2. Thống nhất điểm số và luật trước trận</h2><p>Chốt thể thức tính điểm, luật giao bóng và thời lượng trận giúp cả nhóm tránh tranh luận giữa chừng. Nếu có khác biệt về luật, dừng lại trao đổi bình tĩnh trước khi tiếp tục.</p>
<h2>3. Gọi bóng to, ngắn gọn và trung thực</h2><p>Nếu bóng gần vạch, người ở vị trí quan sát tốt nhất nên gọi rõ “trong” hoặc “ngoài”. Khi không chắc chắn, trao lợi thế cho đối thủ là cách ứng xử đẹp trong trận giao lưu.</p>
<h2>4. Giao tiếp với đồng đội</h2><p>Những câu đơn giản như “của tôi”, “của bạn” hoặc “đổi bên” giúp tránh va chạm và bỏ bóng. Trong đánh đôi, hãy cùng nhau khích lệ thay vì đổ lỗi sau một pha hỏng.</p>
<h2>5. Điều chỉnh lực đánh phù hợp</h2><p>Ghép trận theo trình độ nếu nhóm có quy ước, nhưng đừng xem người mới là mục tiêu để ghi điểm bằng mọi giá. Trận giao lưu có thể là dịp thử cú đánh mới, kéo dài rally và giúp nhau tiến bộ.</p>
<h2>6. Giữ khu vực thi đấu an toàn</h2><p>Đợi rally kết thúc rồi băng qua sân; nhặt bóng rơi khi mọi người đã dừng chơi; không vung vợt gần người khác. Nếu sân đông, đặt túi và chai nước ngoài lối di chuyển.</p>
<h2>7. Chào hỏi và cảm ơn sau trận</h2><p>Một lời chào trước trận, một cái chạm paddle sau trận và lời cảm ơn người chia sân giúp người mới dễ hòa nhập. Ở cộng đồng chơi tại TP.HCM, sự thân thiện thường quan trọng không kém tỷ số.</p>
<h2>Câu hỏi thường gặp</h2>
<h3>Tôi chưa có đồng đội thì có thể tham gia không?</h3><p>Hãy hỏi câu lạc bộ về buổi open play, ghép cặp hoặc giờ phù hợp cho người mới. Nhiều nhóm có cách sắp xếp người chơi đến lẻ.</p>
<h3>Nếu hai bên không thống nhất bóng trong hay ngoài thì sao?</h3><p>Giữ bình tĩnh, ưu tiên tinh thần giao lưu và làm theo quy ước của ban tổ chức. Trận giải có thể áp dụng quy trình gọi bóng chính thức khác với buổi chơi thường.</p>
<p>Pickleball phát triển nhờ cả vận động lẫn kết nối xã hội. Hãy đến sân với tinh thần tôn trọng, cởi mở và sẵn sàng xoay vòng để ai cũng có cơ hội chơi.</p>
HTML,
            ],
            [
                'title' => 'Mới chơi pickleball cần mua gì? Cách chọn vợt, giày và bóng cơ bản',
                'slug' => 'nguoi-moi-choi-pickleball-can-mua-gi',
                'excerpt' => 'Bắt đầu với vợt vừa tay, giày có độ bám và bóng phù hợp mặt sân. Hướng dẫn ưu tiên món cần thiết trước khi đầu tư thêm phụ kiện.',
                'cover_image_path' => 'blog/news/pickleball-dung-cu-co-ban.png',
                'cover_image_alt' => 'Vợt, bóng nhựa đục lỗ, giày thể thao và bình nước chuẩn bị bên cạnh sân pickleball.',
                'meta_title' => 'Người mới chơi pickleball cần mua gì? | BALLBURN',
                'meta_description' => 'Hướng dẫn chọn đồ pickleball cho người mới: vợt vừa tay, giày sân phù hợp, bóng trong nhà hoặc ngoài trời và phụ kiện cần thiết.',
                'category' => 'Dụng cụ & sức khỏe',
                'days_ago' => 6,
                'body' => <<<'HTML'
<p><strong>Tóm tắt nhanh:</strong> Người mới chỉ cần một cây vợt vừa tay, giày thể thao có độ bám phù hợp với mặt sân và bóng đúng loại sân. Hãy ưu tiên cảm giác thoải mái, an toàn và ngân sách trước các thông số nâng cao; nhiều câu lạc bộ tại TP.HCM có thể cho thuê hoặc mượn dụng cụ để thử trước.</p>
<h2>Chọn vợt pickleball như thế nào?</h2>
<p>Thử cầm vợt để xem cán có vừa tay, trọng lượng có khiến cổ tay nhanh mỏi hay không, và mặt vợt có phù hợp với cách bạn muốn điều khiển bóng. Người mới chưa cần chạy theo công nghệ hay thông số quảng cáo. Nếu có thể, hãy mượn thử vài mẫu trong một buổi chơi rồi mới quyết định mua.</p>
<h2>Giày nào phù hợp để chơi pickleball?</h2>
<p>Pickleball có nhiều bước ngang và đổi hướng. Hãy chọn giày chuyên cho sân tennis hoặc pickleball có độ bám và hỗ trợ ngang phù hợp với mặt sân bạn chơi. Giày chạy bộ thường được thiết kế cho chuyển động thẳng, nên không phải lựa chọn lý tưởng cho mọi sân và mọi người.</p>
<h2>Bóng trong nhà và ngoài trời có giống nhau không?</h2>
<p>Bóng pickleball có các lỗ nhưng thiết kế có thể khác nhau theo môi trường sử dụng. Hãy hỏi nơi tổ chức dùng bóng nào và chọn đúng loại được khuyến nghị cho mặt sân; bóng và độ nảy ảnh hưởng đến nhịp rally.</p>
<h2>Phụ kiện nào nên mang theo?</h2>
<ul><li>Nước uống cá nhân và khăn nhỏ.</li><li>Quần áo thể thao thoáng, dễ di chuyển.</li><li>Vớ phù hợp với giày và một túi đựng vợt.</li><li>Kem chống nắng hoặc mũ nếu chơi ngoài trời.</li></ul>
<h2>Câu hỏi thường gặp</h2>
<h3>Người mới có cần mua vợt đắt tiền không?</h3><p>Không. Hãy chọn dụng cụ vừa tay và dễ kiểm soát trước. Sau khi chơi đủ để hiểu sở thích, bạn có thể so sánh kỹ hơn về trọng lượng, tay cầm và cảm giác mặt vợt.</p>
<h3>Có thể mang giày chạy bộ ra sân không?</h3><p>Có thể phụ thuộc nội quy và điều kiện sân, nhưng giày hỗ trợ chuyển động ngang thường phù hợp hơn. Nếu chưa rõ, hỏi câu lạc bộ trước khi đến.</p>
<p><small>Nguồn tham khảo an toàn: <a href="https://orthoinfo.aaos.org/globalassets/pdfs/pickleball-injury-prevention-tips.pdf" target="_blank" rel="noopener noreferrer">AAOS OrthoInfo — Pickleball Injury Prevention Tips</a> khuyến nghị giày tennis hoặc pickleball để có hỗ trợ và độ bám phù hợp. Cảm giác và độ vừa vặn khác nhau theo từng người.</small></p>
HTML,
            ],
            [
                'title' => 'Khởi động trước khi chơi pickleball: quy trình 8 phút dễ áp dụng',
                'slug' => 'khoi-dong-truoc-khi-choi-pickleball',
                'excerpt' => 'Một quy trình khởi động ngắn gồm đi bộ nhanh, vận động khớp và đánh bóng nhẹ giúp cơ thể làm quen với các bước đổi hướng trước trận.',
                'cover_image_path' => 'blog/news/pickleball-khoi-dong-an-toan.png',
                'cover_image_alt' => 'Hai người chơi pickleball khởi động bằng động tác vận động vai và bước ngang cạnh sân.',
                'meta_title' => 'Khởi động pickleball trong 8 phút an toàn | BALLBURN',
                'meta_description' => 'Tham khảo quy trình khởi động pickleball 8 phút gồm đi bộ, vận động linh hoạt và đánh nhẹ; tăng cường độ từ từ, uống đủ nước.',
                'category' => 'Dụng cụ & sức khỏe',
                'days_ago' => 3,
                'body' => <<<'HTML'
<p><strong>Tóm tắt nhanh:</strong> Trước khi chơi pickleball, hãy tăng nhịp vận động từ từ bằng đi bộ nhanh hoặc bước ngang nhẹ, làm nóng vai và chân, sau đó đánh bóng ở cường độ thấp trước khi vào trận. Một lịch khởi động gợi ý trong 8 phút có thể giúp bạn chuẩn bị tốt hơn, nhưng không đảm bảo ngăn ngừa chấn thương.</p>
<h2>Quy trình khởi động 8 phút</h2>
<ol><li><strong>Phút 0–2:</strong> đi bộ nhanh quanh sân, bước tại chỗ hoặc di chuyển nhẹ để cơ thể ấm lên.</li><li><strong>Phút 2–4:</strong> xoay vai, cổ tay và hông nhẹ nhàng trong biên độ thoải mái; không giật mạnh.</li><li><strong>Phút 4–6:</strong> bước ngang ngắn, gập gối nông và chuyển trọng lượng từ chân này sang chân kia có kiểm soát.</li><li><strong>Phút 6–8:</strong> đánh bóng nhẹ ở cự ly gần, tập vài cú forehand/backhand và tăng tốc dần nếu cảm thấy ổn.</li></ol>
<h2>Vì sao không nên vào trận với cường độ tối đa ngay?</h2>
<p>Pickleball có những pha tăng tốc, dừng và đổi hướng. Bắt đầu bằng rally nhẹ giúp bạn cảm nhận mặt sân, độ nảy và nhịp di chuyển trước khi chơi nhanh. AAOS cũng khuyến nghị làm nóng cơ thể, bắt đầu với bài đánh nhẹ, mang giày phù hợp, uống đủ nước và điều chỉnh thời lượng theo khả năng.</p>
<h2>Điều chỉnh theo thời tiết TP.HCM</h2>
<p>Nếu chơi ngoài trời vào ngày nóng, hãy đến sớm để cơ thể thích nghi, chuẩn bị nước và nghỉ khi thấy mệt. Với sân trong nhà, vẫn nên khởi động vì chuyển hướng nhanh có thể xuất hiện ngay từ những pha đầu tiên. Không cố tiếp tục nếu thấy đau bất thường, chóng mặt hoặc khó thở.</p>
<h2>Câu hỏi thường gặp</h2>
<h3>Khởi động 8 phút có đủ cho tất cả mọi người không?</h3><p>Đây chỉ là lịch tham khảo ngắn. Thời gian phù hợp phụ thuộc thể lực, tuổi, kinh nghiệm, thời tiết và tình trạng sức khỏe; hãy kéo dài phần chuẩn bị nếu bạn cần.</p>
<h3>Có nên chơi tiếp khi thấy đau?</h3><p>Hãy dừng lại nếu xuất hiện đau bất thường hoặc triệu chứng khiến bạn lo lắng. Người có bệnh nền, chấn thương cũ hoặc cần hướng dẫn cá nhân nên hỏi chuyên gia y tế trước khi thay đổi chương trình vận động.</p>
<p>Trước buổi chơi tại câu lạc bộ pickleball ở TP.HCM, hãy dành thời gian làm quen sân và hỏi người hướng dẫn về bài khởi động phù hợp. Thói quen đều đặn và tăng cường độ từ từ quan trọng hơn việc cố làm thật nhanh.</p>
<p><small>Nguồn tham khảo: <a href="https://orthoinfo.aaos.org/globalassets/pdfs/pickleball-injury-prevention-tips.pdf" target="_blank" rel="noopener noreferrer">AAOS OrthoInfo — 5 Pickleball Injury Prevention Tips</a>. Nội dung chỉ nhằm cung cấp thông tin chung, không phải chẩn đoán hoặc tư vấn y tế cá nhân.</small></p>
HTML,
            ],
        ];

        foreach ($articles as $article) {
            $category = BlogCategory::query()->where('slug', $categories[$article['category']]['slug'])->firstOrFail();
            $publishedAt = now()->subDays($article['days_ago'])->setTime(9, 0);

            $post = BlogPost::query()->updateOrCreate(
                ['slug' => $article['slug']],
                [
                    'author_id' => null,
                    'title' => $article['title'],
                    'excerpt' => $article['excerpt'],
                    'body' => $this->plainTextBody($article['body']),
                    'cover_image_path' => $article['cover_image_path'],
                    'cover_image_alt' => $article['cover_image_alt'],
                    'meta_title' => $article['meta_title'],
                    'meta_description' => $article['meta_description'],
                    'status' => 'PUBLISHED',
                    'published_at' => $publishedAt,
                ],
            );

            $post->categories()->sync([$category->id]);
        }
    }

    private function plainTextBody(string $html): string
    {
        $text = preg_replace_callback(
            '~<a\b[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)</a>~is',
            fn (array $match): string => strip_tags($match[2]).' ('.$match[1].')',
            $html,
        );
        $text = preg_replace('~</?(?:h[1-6]|p|div|section|ol|ul)[^>]*>~i', "\n\n", $text ?? $html);
        $text = preg_replace('~<li\b[^>]*>~i', "\n• ", $text ?? '');
        $text = preg_replace('~<br\s*/?>~i', "\n", $text ?? '');
        $text = html_entity_decode(strip_tags($text ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[ \t]+/u', ' ', $text);
        $text = preg_replace('/\n[ \t]*\n(?:[ \t]*\n)+/u', "\n\n", $text ?? '');

        return trim($text ?? '');
    }
}
