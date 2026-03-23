// backend/src/compliance/compliance.controller.ts
import { Controller, Get, Post, Body, Req, UseGuards, BadRequestException } from '@nestjs/common';
import { ComplianceService } from './compliance.service';
import { ComplianceType } from '@prisma/client';
import { PrismaService } from '../prisma/prisma.service';

@Controller('api/compliance')
export class ComplianceController {
  constructor(
    private readonly complianceService: ComplianceService,
    private readonly prisma: PrismaService
  ) {}

  /**
   * Получение списка интеграций текущего тенанта.
   */
  @Get()
  async getIntegrations(@Req() req: any) {
    const tenantId = req.user?.tenantId || 'test-tenant-id'; // В реальном приложении берем из JWT
    
    const results = await this.prisma.complianceIntegration.findMany({
      where: { tenantId },
      select: {
        id: true,
        type: true,
        inn: true,
        status: true,
        lastCheckedAt: true,
        errorMessage: true,
      }
    });

    return results;
  }

  /**
   * Тест подключения к госсистеме.
   */
  @Post('test-connection')
  async testConnection(@Body() body: { type: ComplianceType; inn: string; token: string }, @Req() req: any) {
    const tenantId = req.user?.tenantId || 'test-tenant-id';
    
    if (!body.inn || !body.token || !body.type) {
      throw new BadRequestException('Все поля (Тип, ИНН, Токен) обязательны для тестирования.');
    }

    // Вызов сервиса для реального HTTP-запроса к Честный ЗНАК/Меркурий
    return this.complianceService.testConnection(body.type, body.inn, body.token, tenantId);
  }

  /**
   * Сохранение новой или обновление существующей интеграции.
   */
  @Post('connect')
  async connectIntegration(@Body() body: { type: ComplianceType; inn: string; token: string }, @Req() req: any) {
    const tenantId = req.user?.tenantId || 'test-tenant-id';

    if (!body.inn || !body.token || !body.type) {
        throw new BadRequestException('Недостаточно данных для сохранения интеграции.');
    }

    return this.complianceService.connectIntegration(tenantId, body.type, body.inn, body.token);
  }
}
