Новая заявка с лендинга «{{ config('app.name') }}»

Имя: {{ $clientName }}
Телефон: {{ $phone }}

Район: {{ $districtTitle }}
@if($districtType)
Тип: {{ $districtType }}
@endif
Код района: {{ $districtId }}
@if($page)
Страница: {{ $page }}
@endif

---
Письмо отправлено автоматически с формы «Интерес к району» на карте главной страницы.
