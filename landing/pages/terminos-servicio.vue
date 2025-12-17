<script setup lang="ts">
// SEO
useHead({
  title: 'Términos y Condiciones de Servicio - Tavira',
  meta: [
    { name: 'description', content: 'Términos y condiciones de uso de la plataforma Tavira para administración de conjuntos residenciales.' },
    { property: 'og:title', content: 'Términos y Condiciones - Tavira' },
    { property: 'og:description', content: 'Conoce los términos y condiciones de uso de nuestra plataforma.' },
  ],
});

const lastUpdated = '16 de diciembre de 2025';
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50">
    <!-- Header -->
    <section class="bg-gradient-to-br from-indigo-900 to-blue-900 text-white py-20 px-4">
      <div class="container mx-auto max-w-4xl text-center">
        <h1 class="text-4xl md:text-5xl font-bold mb-6">
          Términos y Condiciones de Servicio
        </h1>
        <p class="text-xl opacity-90">
          Condiciones de uso de la plataforma Tavira
        </p>
        <p class="mt-4 text-sm opacity-75">
          Última actualización: {{ lastUpdated }}
        </p>
      </div>
    </section>

    <!-- Content -->
    <section class="py-16 px-4">
      <div class="container mx-auto max-w-4xl">
        <div class="bg-white rounded-2xl shadow-xl p-8 md:p-12 space-y-12">

          <!-- 1. Aceptación -->
          <div class="space-y-4">
            <h2 class="text-2xl font-bold text-indigo-900 flex items-center">
              <span class="bg-indigo-100 text-indigo-600 rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold mr-3">1</span>
              Aceptación de los Términos
            </h2>
            <p class="text-gray-700">
              Al acceder y utilizar la plataforma Tavira (en adelante "la Plataforma"), usted acepta estar sujeto a estos Términos y Condiciones de Servicio,
              así como a nuestra <NuxtLink to="/politica-privacidad" class="text-indigo-600 hover:underline">Política de Privacidad</NuxtLink>.
              Si no está de acuerdo con alguno de estos términos, no debe utilizar la Plataforma.
            </p>
            <p class="text-gray-700">
              Estos términos constituyen un acuerdo legal vinculante entre usted (ya sea como persona natural o en representación de una persona jurídica)
              y TAVIRA S.A.S., sociedad comercial constituida bajo las leyes de la República de Colombia.
            </p>
          </div>

          <!-- 2. Descripción del Servicio -->
          <div class="space-y-4">
            <h2 class="text-2xl font-bold text-indigo-900 flex items-center">
              <span class="bg-indigo-100 text-indigo-600 rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold mr-3">2</span>
              Descripción del Servicio
            </h2>
            <p class="text-gray-700">
              Tavira es una plataforma tecnológica de software como servicio (SaaS) diseñada para la gestión integral de conjuntos residenciales
              y propiedades horizontales en Colombia. La Plataforma ofrece, entre otros:
            </p>
            <ul class="text-gray-700 space-y-2 ml-4">
              <li class="flex items-start space-x-2">
                <span class="text-indigo-600">•</span>
                <span>Gestión administrativa de copropiedades</span>
              </li>
              <li class="flex items-start space-x-2">
                <span class="text-indigo-600">•</span>
                <span>Facturación y cobro de cuotas de administración</span>
              </li>
              <li class="flex items-start space-x-2">
                <span class="text-indigo-600">•</span>
                <span>Control de acceso y seguridad</span>
              </li>
              <li class="flex items-start space-x-2">
                <span class="text-indigo-600">•</span>
                <span>Comunicación entre administración y residentes</span>
              </li>
              <li class="flex items-start space-x-2">
                <span class="text-indigo-600">•</span>
                <span>Red de seguridad colaborativa (Red Tavira)</span>
              </li>
              <li class="flex items-start space-x-2">
                <span class="text-indigo-600">•</span>
                <span>Aplicaciones móviles para iOS y Android</span>
              </li>
            </ul>
          </div>

          <!-- 3. Usuarios -->
          <div class="space-y-4">
            <h2 class="text-2xl font-bold text-indigo-900 flex items-center">
              <span class="bg-indigo-100 text-indigo-600 rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold mr-3">3</span>
              Tipos de Usuarios
            </h2>
            <p class="text-gray-700">
              La Plataforma reconoce los siguientes tipos de usuarios:
            </p>
            <div class="grid md:grid-cols-2 gap-4">
              <div class="bg-gray-50 p-4 rounded-lg">
                <strong class="text-indigo-700">Administradores:</strong>
                <p class="text-sm text-gray-600 mt-1">Personas naturales o jurídicas contratadas para la administración del conjunto residencial.</p>
              </div>
              <div class="bg-gray-50 p-4 rounded-lg">
                <strong class="text-indigo-700">Copropietarios:</strong>
                <p class="text-sm text-gray-600 mt-1">Titulares de derechos de propiedad sobre unidades privadas de la copropiedad.</p>
              </div>
              <div class="bg-gray-50 p-4 rounded-lg">
                <strong class="text-indigo-700">Residentes:</strong>
                <p class="text-sm text-gray-600 mt-1">Personas que habitan en las unidades privadas (propietarios, arrendatarios, tenedores).</p>
              </div>
              <div class="bg-gray-50 p-4 rounded-lg">
                <strong class="text-indigo-700">Consejo de Administración:</strong>
                <p class="text-sm text-gray-600 mt-1">Miembros elegidos por la asamblea para supervisar la administración.</p>
              </div>
            </div>
          </div>

          <!-- 4. Obligaciones -->
          <div class="space-y-4">
            <h2 class="text-2xl font-bold text-indigo-900 flex items-center">
              <span class="bg-indigo-100 text-indigo-600 rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold mr-3">4</span>
              Obligaciones del Usuario
            </h2>
            <p class="text-gray-700">Al utilizar la Plataforma, usted se compromete a:</p>
            <ul class="text-gray-700 space-y-2">
              <li class="flex items-start space-x-2">
                <span class="text-green-600">✓</span>
                <span>Proporcionar información veraz, completa y actualizada.</span>
              </li>
              <li class="flex items-start space-x-2">
                <span class="text-green-600">✓</span>
                <span>Mantener la confidencialidad de sus credenciales de acceso.</span>
              </li>
              <li class="flex items-start space-x-2">
                <span class="text-green-600">✓</span>
                <span>Utilizar la Plataforma únicamente para los fines previstos.</span>
              </li>
              <li class="flex items-start space-x-2">
                <span class="text-green-600">✓</span>
                <span>Respetar los derechos de propiedad intelectual de Tavira.</span>
              </li>
              <li class="flex items-start space-x-2">
                <span class="text-green-600">✓</span>
                <span>No realizar actividades que comprometan la seguridad de la Plataforma.</span>
              </li>
              <li class="flex items-start space-x-2">
                <span class="text-green-600">✓</span>
                <span>Cumplir con la legislación colombiana aplicable.</span>
              </li>
            </ul>
          </div>

          <!-- 5. Prohibiciones -->
          <div class="space-y-4">
            <h2 class="text-2xl font-bold text-indigo-900 flex items-center">
              <span class="bg-indigo-100 text-indigo-600 rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold mr-3">5</span>
              Conductas Prohibidas
            </h2>
            <p class="text-gray-700">Queda expresamente prohibido:</p>
            <ul class="text-gray-700 space-y-2">
              <li class="flex items-start space-x-2">
                <span class="text-red-600">✗</span>
                <span>Acceder a cuentas de otros usuarios sin autorización.</span>
              </li>
              <li class="flex items-start space-x-2">
                <span class="text-red-600">✗</span>
                <span>Intentar vulnerar los sistemas de seguridad de la Plataforma.</span>
              </li>
              <li class="flex items-start space-x-2">
                <span class="text-red-600">✗</span>
                <span>Utilizar la Plataforma para actividades ilegales o fraudulentas.</span>
              </li>
              <li class="flex items-start space-x-2">
                <span class="text-red-600">✗</span>
                <span>Compartir información falsa o engañosa.</span>
              </li>
              <li class="flex items-start space-x-2">
                <span class="text-red-600">✗</span>
                <span>Reproducir, distribuir o modificar el software sin autorización.</span>
              </li>
              <li class="flex items-start space-x-2">
                <span class="text-red-600">✗</span>
                <span>Realizar ingeniería inversa del código fuente.</span>
              </li>
            </ul>
          </div>

          <!-- 6. Propiedad Intelectual -->
          <div class="space-y-4">
            <h2 class="text-2xl font-bold text-indigo-900 flex items-center">
              <span class="bg-indigo-100 text-indigo-600 rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold mr-3">6</span>
              Propiedad Intelectual
            </h2>
            <p class="text-gray-700">
              Todos los derechos de propiedad intelectual sobre la Plataforma, incluyendo pero sin limitarse al software, diseños, marcas,
              logotipos, textos, gráficos e interfaces, son propiedad exclusiva de TAVIRA S.A.S. o sus licenciantes.
            </p>
            <p class="text-gray-700">
              El uso de la Plataforma no otorga al usuario ningún derecho de propiedad sobre la misma, excepto la licencia limitada,
              no exclusiva y revocable para utilizar el servicio conforme a estos términos.
            </p>
          </div>

          <!-- 7. Pagos y Facturación -->
          <div class="space-y-4">
            <h2 class="text-2xl font-bold text-indigo-900 flex items-center">
              <span class="bg-indigo-100 text-indigo-600 rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold mr-3">7</span>
              Pagos y Facturación
            </h2>
            <p class="text-gray-700">
              Los servicios de Tavira se ofrecen mediante planes de suscripción. Los precios, características y condiciones de cada plan
              se encuentran disponibles en nuestra página web y pueden ser modificados con previo aviso.
            </p>
            <ul class="text-gray-700 space-y-2">
              <li class="flex items-start space-x-2">
                <span class="text-indigo-600">•</span>
                <span>Los pagos se realizan por adelantado según el período de facturación acordado.</span>
              </li>
              <li class="flex items-start space-x-2">
                <span class="text-indigo-600">•</span>
                <span>Las facturas se emiten conforme a la normativa tributaria colombiana.</span>
              </li>
              <li class="flex items-start space-x-2">
                <span class="text-indigo-600">•</span>
                <span>El incumplimiento en el pago puede resultar en la suspensión del servicio.</span>
              </li>
            </ul>
          </div>

          <!-- 8. Disponibilidad -->
          <div class="space-y-4">
            <h2 class="text-2xl font-bold text-indigo-900 flex items-center">
              <span class="bg-indigo-100 text-indigo-600 rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold mr-3">8</span>
              Disponibilidad del Servicio
            </h2>
            <p class="text-gray-700">
              Tavira se compromete a mantener una disponibilidad del servicio del 99.9%. Sin embargo, la Plataforma puede experimentar
              interrupciones por:
            </p>
            <ul class="text-gray-700 space-y-2">
              <li class="flex items-start space-x-2">
                <span class="text-indigo-600">•</span>
                <span>Mantenimiento programado (notificado con antelación).</span>
              </li>
              <li class="flex items-start space-x-2">
                <span class="text-indigo-600">•</span>
                <span>Actualizaciones de seguridad urgentes.</span>
              </li>
              <li class="flex items-start space-x-2">
                <span class="text-indigo-600">•</span>
                <span>Causas de fuerza mayor o caso fortuito.</span>
              </li>
              <li class="flex items-start space-x-2">
                <span class="text-indigo-600">•</span>
                <span>Fallas en servicios de terceros (proveedores de infraestructura, internet).</span>
              </li>
            </ul>
          </div>

          <!-- 9. Limitación de Responsabilidad -->
          <div class="space-y-4">
            <h2 class="text-2xl font-bold text-indigo-900 flex items-center">
              <span class="bg-indigo-100 text-indigo-600 rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold mr-3">9</span>
              Limitación de Responsabilidad
            </h2>
            <p class="text-gray-700">
              En la máxima medida permitida por la ley colombiana, TAVIRA S.A.S. no será responsable por:
            </p>
            <ul class="text-gray-700 space-y-2">
              <li class="flex items-start space-x-2">
                <span class="text-indigo-600">•</span>
                <span>Daños indirectos, incidentales o consecuentes.</span>
              </li>
              <li class="flex items-start space-x-2">
                <span class="text-indigo-600">•</span>
                <span>Pérdida de datos debido a uso incorrecto de la Plataforma.</span>
              </li>
              <li class="flex items-start space-x-2">
                <span class="text-indigo-600">•</span>
                <span>Decisiones tomadas basadas en la información de la Plataforma.</span>
              </li>
              <li class="flex items-start space-x-2">
                <span class="text-indigo-600">•</span>
                <span>Acciones u omisiones de los usuarios de la Plataforma.</span>
              </li>
            </ul>
            <p class="text-gray-700">
              La responsabilidad total de Tavira estará limitada al monto pagado por el usuario durante los últimos 12 meses de servicio.
            </p>
          </div>

          <!-- 10. Terminación -->
          <div class="space-y-4">
            <h2 class="text-2xl font-bold text-indigo-900 flex items-center">
              <span class="bg-indigo-100 text-indigo-600 rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold mr-3">10</span>
              Terminación del Servicio
            </h2>
            <p class="text-gray-700">
              El contrato de servicio puede terminarse por:
            </p>
            <ul class="text-gray-700 space-y-2">
              <li class="flex items-start space-x-2">
                <span class="text-indigo-600">•</span>
                <span>Solicitud del usuario con 30 días de anticipación.</span>
              </li>
              <li class="flex items-start space-x-2">
                <span class="text-indigo-600">•</span>
                <span>Incumplimiento de estos términos por parte del usuario.</span>
              </li>
              <li class="flex items-start space-x-2">
                <span class="text-indigo-600">•</span>
                <span>Falta de pago por más de 30 días.</span>
              </li>
              <li class="flex items-start space-x-2">
                <span class="text-indigo-600">•</span>
                <span>Mutuo acuerdo entre las partes.</span>
              </li>
            </ul>
            <p class="text-gray-700">
              Tras la terminación, el usuario tendrá un período de 30 días para exportar sus datos. Después de este período,
              los datos serán eliminados conforme a nuestra política de retención.
            </p>
          </div>

          <!-- 11. Ley Aplicable -->
          <div class="space-y-4">
            <h2 class="text-2xl font-bold text-indigo-900 flex items-center">
              <span class="bg-indigo-100 text-indigo-600 rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold mr-3">11</span>
              Ley Aplicable y Jurisdicción
            </h2>
            <p class="text-gray-700">
              Estos términos se regirán e interpretarán de acuerdo con las leyes de la República de Colombia.
              Cualquier controversia será sometida a la jurisdicción de los tribunales competentes de la ciudad de Bogotá D.C., Colombia.
            </p>
            <p class="text-gray-700">
              Las partes acuerdan agotar los mecanismos de solución directa antes de acudir a la vía judicial,
              pudiendo recurrir a centros de conciliación y arbitraje reconocidos.
            </p>
          </div>

          <!-- 12. Modificaciones -->
          <div class="space-y-4">
            <h2 class="text-2xl font-bold text-indigo-900 flex items-center">
              <span class="bg-indigo-100 text-indigo-600 rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold mr-3">12</span>
              Modificaciones a los Términos
            </h2>
            <p class="text-gray-700">
              Tavira se reserva el derecho de modificar estos términos en cualquier momento. Las modificaciones serán notificadas a través de:
            </p>
            <ul class="text-gray-700 space-y-2">
              <li class="flex items-start space-x-2">
                <span class="text-indigo-600">•</span>
                <span>Publicación en el sitio web con la fecha de actualización.</span>
              </li>
              <li class="flex items-start space-x-2">
                <span class="text-indigo-600">•</span>
                <span>Correo electrónico a los usuarios registrados.</span>
              </li>
              <li class="flex items-start space-x-2">
                <span class="text-indigo-600">•</span>
                <span>Aviso dentro de la Plataforma.</span>
              </li>
            </ul>
            <p class="text-gray-700">
              El uso continuado de la Plataforma después de la notificación constituye aceptación de los nuevos términos.
            </p>
          </div>

          <!-- 13. Contacto -->
          <div class="space-y-4">
            <h2 class="text-2xl font-bold text-indigo-900 flex items-center">
              <span class="bg-indigo-100 text-indigo-600 rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold mr-3">13</span>
              Contacto
            </h2>
            <p class="text-gray-700">
              Para cualquier consulta relacionada con estos términos, puede contactarnos a través de:
            </p>
            <div class="bg-gray-50 rounded-xl p-6 space-y-2">
              <p><strong>TAVIRA S.A.S.</strong></p>
              <p>📧 legal@tavira.com.co</p>
              <p>📱 WhatsApp: +57 300 123 4567</p>
              <p>🌐 https://tavira.com.co</p>
            </div>
          </div>

          <!-- Aceptación -->
          <div class="bg-gradient-to-br from-indigo-100 to-blue-100 rounded-xl p-8 text-center">
            <h3 class="text-xl font-bold text-indigo-900 mb-4">Aceptación de los Términos</h3>
            <p class="text-gray-700 mb-4">
              Al utilizar la plataforma Tavira, usted declara haber leído, comprendido y aceptado
              estos Términos y Condiciones de Servicio en su totalidad.
            </p>
            <p class="text-sm text-gray-600">
              Documento vigente desde: {{ lastUpdated }}
            </p>
          </div>

        </div>

        <!-- Back to Home -->
        <div class="text-center mt-8">
          <NuxtLink
            to="/"
            class="inline-flex items-center space-x-2 text-indigo-600 hover:text-indigo-800 font-medium transition-colors"
          >
            <span>←</span>
            <span>Volver al inicio</span>
          </NuxtLink>
        </div>
      </div>
    </section>

    <!-- Footer -->
    <footer class="bg-slate-900 text-white py-8 px-4">
      <div class="container mx-auto max-w-4xl text-center">
        <p class="text-gray-400">
          &copy; {{ new Date().getFullYear() }} Tavira S.A.S. Todos los derechos reservados.
        </p>
      </div>
    </footer>
  </div>
</template>
