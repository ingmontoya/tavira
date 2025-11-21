export default defineEventHandler(async (event) => {
  const config = useRuntimeConfig();

  try {
    // Read the request body
    const body = await readBody(event);

    // Validate required fields
    const requiredFields = ['name', 'email', 'phone', 'conjunto_name', 'num_units', 'role'];
    for (const field of requiredFields) {
      if (!body[field]) {
        throw createError({
          statusCode: 400,
          statusMessage: `Missing required field: ${field}`
        });
      }
    }

    // Validate email format
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(body.email)) {
      throw createError({
        statusCode: 400,
        statusMessage: 'Invalid email format'
      });
    }

    // Build description
    let description = "Solicitud de demostración desde Landing Page\n\n";
    description += `Cargo en el Consejo: ${body.role}\n`;
    description += `Conjunto: ${body.conjunto_name}\n`;
    description += `Número de Unidades: ${body.num_units}\n`;

    if (body.message) {
      description += `\nMensaje adicional:\n${body.message}`;
    }

    // Prepare payload for Perfex CRM API
    const perfexPayload = {
      token: config.perfexApiToken,
      name: body.name,
      email: body.email,
      phonenumber: body.phone,
      company: body.conjunto_name,
      title: body.role,
      description: description,
      country: 'Colombia',
      tags: 'Consejo,Landing Page,Prospecto',
      source: body.lead_source || 'Website - Landing Consejos',
      custom_field_conjunto_name: body.conjunto_name,
      custom_field_num_units: body.num_units,
      custom_field_role: body.role,
      blockcreatelead: 0, // Allow duplicate emails
      blockupdatelead: 0  // Allow updates
    };

    // Send to Perfex CRM API
    const perfexResponse = await $fetch(`${config.perfexBaseUrl}/api/leads`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
      body: perfexPayload
    });

    console.log('Lead created successfully in Perfex CRM:', {
      email: body.email,
      conjunto: body.conjunto_name,
      response: perfexResponse
    });

    return {
      success: true,
      message: 'Lead created successfully',
      data: perfexResponse
    };
  } catch (error: any) {
    console.error('Error creating lead in Perfex CRM:', error);

    // Log the error but don't expose internal details to the client
    if (error.statusCode) {
      throw error;
    }

    // For Perfex API errors, return a generic message
    throw createError({
      statusCode: 500,
      statusMessage: 'Error processing your request. Please try again.'
    });
  }
});
